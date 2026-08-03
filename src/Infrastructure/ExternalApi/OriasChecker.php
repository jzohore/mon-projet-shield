<?php

declare(strict_types=1);

namespace App\Infrastructure\ExternalApi;

use App\Domain\Workspace\Gateway\OriasCheckerInterface;
use App\Domain\Workspace\ValueObject\OriasStatusResult;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Webmozart\Assert\Assert;

final readonly class OriasChecker implements OriasCheckerInterface
{
    private const string BASE_URL = 'https://www.orias.fr/home/showIntermediaire/';

    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    public function checkNumber(string $oriasNumber): OriasStatusResult
    {
        $cleanNumber = preg_replace('/\s+/', '', trim($oriasNumber));

        if (empty($cleanNumber)) {
            return OriasStatusResult::invalid($oriasNumber, 'Le numéro ORIAS fourni est vide.');
        }

        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . $cleanNumber, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ],
                'timeout' => 10.0,
            ]);

            if (404 === $response->getStatusCode()) {
                return OriasStatusResult::invalid($cleanNumber, 'Aucun intermédiaire trouvé sur l\'ORIAS pour ce numéro.');
            }

            if (200 !== $response->getStatusCode()) {
                return OriasStatusResult::invalid($cleanNumber, sprintf('Le serveur ORIAS a répondu avec le code HTTP %d.', $response->getStatusCode()));
            }

            $html = $response->getContent();
            $crawler = new Crawler($html);

            if ($crawler->filter('.no-result, .alert-danger')->count() > 0) {
                return OriasStatusResult::invalid($cleanNumber, 'Intermédiaire introuvable ou numéro invalide.');
            }

            // 1. Liste des critères principaux
            $extractedData = [];
            $crawler->filter('.listCritere li')->each(static function (Crawler $node) use (&$extractedData): void {
                $spans = $node->filter('span');
                if ($spans->count() >= 2) {
                    $label = trim($spans->eq(0)->text());
                    $value = trim($spans->eq(1)->text());
                    $extractedData[$label] = preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $value);
                }
            });

            // 2. Nom & Statut
            $legalName = $extractedData['Sigle, Enseigne, Nom commercial']
                ?? $extractedData['Dénomination']
                ?? $extractedData['Nom, Prénoms']
                ?? null;

            if (null === $legalName) {
                $h1Node = $crawler->filter('h1.bigTitle');
                if ($h1Node->count() > 0) {
                    $legalName = trim($h1Node->text());
                }
            }

            $statusText = $extractedData['Etat & Inscriptions'] ?? 'Inscrit';

            // 3. Catégories
            $categories = [];
            $crawler->filter('.leftSideResult .item')->each(static function (Crawler $node) use (&$categories): void {
                $lines = explode("\n", trim($node->text()));
                $sigle = trim($lines[0]);
                if ('' !== $sigle) {
                    $categories[] = $sigle;
                }
            });

            // 4. EXTRACTION DES ASSOCIATIONS (Nouveau)
            $associations = [];
            $crawler->filter('.detailResult')->each(static function (Crawler $node) use (&$associations): void {
                $header = $node->filter('.detailResult--header h4.title');

                // On vérifie que c'est bien le bloc des Associations (et non celui des Mandats)
                if ($header->count() > 0 && false !== stripos($header->text(), 'Association')) {
                    // On boucle sur les lignes du tableau contenu dans ce bloc
                    $node->filter('.detailResult--content tbody tr')->each(static function (Crawler $tr) use (&$associations): void {
                        $tds = $tr->filter('td');

                        // La 2ème colonne (index 1) contient la dénomination de l'association
                        if ($tds->count() >= 2) {
                            $assoName = trim($tds->eq(1)->text());
                            if ('' !== $assoName) {
                                // Parfois l'ORIAS met le SIREN à la fin du nom (ex: "CNCEF Assurance 878643915")
                                // On peut nettoyer les chiffres à la fin si on veut un nom propre :
                                $assoName = preg_replace('/\s\d+$/', '', $assoName);
                                Assert::notNull($assoName);
                                $associations[] = trim($assoName);
                            }
                        }
                    });
                }
            });

            if ([] === $extractedData && null === $legalName) {
                return OriasStatusResult::invalid($cleanNumber, 'Format de la page ORIAS non reconnu.');
            }

            return new OriasStatusResult(
                oriasNumber: $cleanNumber,
                isValid: true,
                registrationStatus: trim($statusText),
                legalName: $legalName,
                categories: array_unique(array_filter($categories)),
                associations: array_unique(array_filter($associations)) // Envoi du tableau dédoublonné
            );
        } catch (\Throwable $e) {
            return OriasStatusResult::invalid($cleanNumber, 'Erreur de lecture du DOM ORIAS : ' . $e->getMessage());
        }
    }
}
