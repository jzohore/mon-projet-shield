<?php

declare(strict_types=1);

namespace App\Infrastructure\ExternalApi;

use App\Domain\Workspace\Gateway\OriasCheckerInterface;
use App\Domain\Workspace\ValueObject\OriasStatusResult;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Lecture du registre public ORIAS via la fiche « showIntermediaire/{SIREN} ».
 *
 * ⚠️ L'endpoint résout le segment d'URL comme un **SIREN** (9 chiffres), pas
 * comme le n° d'immatriculation ORIAS (8 chiffres). Il renvoie :
 *   - le HTML de la fiche si le SIREN est connu ;
 *   - un JSON `{"error": "..."}` sinon.
 *
 * ⚠️ Dépend de la structure HTML du site (aucun contrat d'API). Un échec de
 * lecture donne {@see OriasStatusResult::unavailable()} (à réessayer) ; une
 * réponse ORIAS explicite (404, JSON d'erreur, fiche « Radié ») donne
 * {@see OriasStatusResult::notRegistered()}.
 */
final readonly class OriasChecker implements OriasCheckerInterface
{
    private const string BASE_URL = 'https://www.orias.fr/home/showIntermediaire/';

    /** L'endpoint attend un SIREN : 9 chiffres. */
    private const string SIREN_PATTERN = '/^\d{9}$/';

    private const float TIMEOUT = 10.0;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param string $oriasNumber un SIREN (9 chiffres, espaces tolérés)
     */
    public function checkNumber(string $oriasNumber): OriasStatusResult
    {
        $siren = (string) preg_replace('/\s+/', '', trim($oriasNumber));

        if (1 !== preg_match(self::SIREN_PATTERN, $siren)) {
            return OriasStatusResult::notRegistered(
                $siren,
                'Le numéro fourni n\'a pas le format d\'un SIREN (9 chiffres attendus pour interroger l\'ORIAS).',
            );
        }

        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . $siren, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ],
                'timeout' => self::TIMEOUT,
            ]);

            $statusCode = $response->getStatusCode();

            if (404 === $statusCode) {
                return OriasStatusResult::notRegistered($siren, 'Aucun intermédiaire trouvé sur l\'ORIAS pour ce SIREN.');
            }

            if (200 !== $statusCode) {
                $this->logger->warning('Réponse inattendue du registre ORIAS.', ['siren' => $siren, 'status' => $statusCode]);

                return OriasStatusResult::unavailable($siren, sprintf('Le registre ORIAS a répondu avec le code HTTP %d.', $statusCode));
            }

            $body = $response->getContent();
        } catch (TransportExceptionInterface $e) {
            $this->logger->warning('Registre ORIAS injoignable.', ['siren' => $siren, 'error' => $e->getMessage()]);

            return OriasStatusResult::unavailable($siren, 'Le registre ORIAS est momentanément injoignable.');
        }

        // ORIAS renvoie un JSON d'erreur quand le SIREN n'est pas au registre.
        if (null !== $jsonError = $this->jsonError($body)) {
            return OriasStatusResult::notRegistered($siren, $jsonError);
        }

        try {
            return $this->parseIntermediary($body, $siren);
        } catch (\Throwable $e) {
            $this->logger->warning('Fiche ORIAS illisible (structure inattendue).', [
                'siren' => $siren,
                'content_length' => \strlen($body),
                'content_sha1' => sha1($body),
                'error' => $e->getMessage(),
            ]);

            return OriasStatusResult::unavailable($siren, 'La fiche ORIAS n\'a pas pu être analysée (structure inattendue).');
        }
    }

    private function jsonError(string $body): ?string
    {
        $trimmed = ltrim($body);
        if ('' === $trimmed || '{' !== $trimmed[0]) {
            return null;
        }

        $decoded = json_decode($trimmed, true);

        return \is_array($decoded) && \is_string($decoded['error'] ?? null) ? $decoded['error'] : null;
    }

    private function parseIntermediary(string $html, string $siren): OriasStatusResult
    {
        $crawler = new Crawler($html);

        if ($crawler->filter('.no-result, .alert-danger')->count() > 0) {
            return OriasStatusResult::notRegistered($siren, 'Intermédiaire introuvable ou numéro invalide.');
        }

        $criteria = $this->extractCriteria($crawler);
        $legalName = $this->extractLegalName($crawler, $criteria);

        // Fiche atteinte mais aucun champ reconnu : probable dérive de structure → à réessayer.
        if ([] === $criteria && null === $legalName) {
            throw new \RuntimeException('Aucun critère ni dénomination extraits de la fiche.');
        }

        $status = trim($criteria['Etat & Inscriptions'] ?? '');

        // « Radié 0 » : connu du registre mais plus autorisé à exercer.
        if (str_contains(mb_strtolower($status), 'radié')) {
            return OriasStatusResult::notRegistered($siren, sprintf('Intermédiaire radié du registre ORIAS (%s).', $status));
        }

        return OriasStatusResult::valid(
            oriasNumber: $siren,
            registrationStatus: '' !== $status ? $status : 'Inscrit',
            legalName: $legalName,
            categories: $this->extractCategories($crawler),
            associations: $this->extractAssociations($crawler),
            registeredOriasNumber: $this->normalizeDigits($criteria['N° Orias'] ?? null),
        );
    }

    /**
     * @return array<string, string>
     */
    private function extractCriteria(Crawler $crawler): array
    {
        $criteria = [];
        $crawler->filter('.listCritere li')->each(static function (Crawler $node) use (&$criteria): void {
            $spans = $node->filter('span');
            if ($spans->count() >= 2) {
                $label = trim($spans->eq(0)->text());
                $criteria[$label] = (string) preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', trim($spans->eq(1)->text()));
            }
        });

        return $criteria;
    }

    /**
     * @param array<string, string> $criteria
     */
    private function extractLegalName(Crawler $crawler, array $criteria): ?string
    {
        $name = $criteria['Sigle, Enseigne, Nom commercial']
            ?? $criteria['Dénomination']
            ?? $criteria['Nom, Prénoms']
            ?? null;

        if (null !== $name && '' !== $name) {
            return $name;
        }

        $h1 = $crawler->filter('h1.bigTitle');

        return $h1->count() > 0 ? trim($h1->text()) : null;
    }

    /**
     * Libellés des catégories d'inscription (COA, MIA, IOBSP, …).
     *
     * @return list<string>
     */
    private function extractCategories(Crawler $crawler): array
    {
        $categories = [];

        // Structure actuelle : .mainResult .typeInterm .infoBulle
        $crawler->filter('.mainResult .typeInterm .infoBulle')->each(static function (Crawler $node) use (&$categories): void {
            $label = trim($node->text());
            if ('' !== $label) {
                $categories[] = $label;
            }
        });

        // Repli sur l'ancienne structure au cas où certaines fiches ne seraient pas migrées.
        if ([] === $categories) {
            $crawler->filter('.leftSideResult .item')->each(static function (Crawler $node) use (&$categories): void {
                $sigle = trim(explode("\n", trim($node->text()))[0]);
                if ('' !== $sigle) {
                    $categories[] = $sigle;
                }
            });
        }

        return array_values(array_unique($categories));
    }

    /**
     * Associations professionnelles agréées. Structure incertaine sur le site
     * actuel : on tente l'ancien bloc, sinon liste vide.
     *
     * @return list<string>
     */
    private function extractAssociations(Crawler $crawler): array
    {
        $associations = [];
        $crawler->filter('.detailResult')->each(static function (Crawler $node) use (&$associations): void {
            $header = $node->filter('.detailResult--header h4.title');
            if (0 === $header->count() || false === stripos($header->text(), 'Association')) {
                return;
            }

            $node->filter('.detailResult--content tbody tr')->each(static function (Crawler $tr) use (&$associations): void {
                $cells = $tr->filter('td');
                if ($cells->count() < 2) {
                    return;
                }

                $name = trim((string) preg_replace('/\s\d+$/', '', trim($cells->eq(1)->text())));
                if ('' !== $name) {
                    $associations[] = $name;
                }
            });
        });

        return array_values(array_unique($associations));
    }

    private function normalizeDigits(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return \is_string($digits) && '' !== $digits ? $digits : null;
    }
}
