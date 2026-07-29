<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Application\ExternalAPI\Siren\SirenResult;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class SiretSearchService
{
    public function __construct(
        private HttpClientInterface $client,
    ) {
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function search(string $searchQuery): array
    {
        $response = $this->client->request('GET', 'https://recherche-entreprises.api.gouv.fr/search', [
            'query' => [
                'q' => trim($searchQuery),
                'page' => 1,
                'per_page' => 3,
            ],
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
        $data = $response->toArray();

        // On appelle notre service qui va interroger l'API de l'État
        return $data['results'] ?? [];
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function verifyStatus(string $siret, ?string $name = null): SirenResult
    {
        // 1. Récupérer les données via HTTP
        $entreprise = $this->search($siret);
        $etatAdministratif = $entreprise[0]['etat_administratif'] ?? null;
        $nombreEtablissementsOuverts = $entreprise[0]['nombre_etablissements_ouverts'] ?? null;
        $matchingEtablissements = $entreprise[0]['matching_etablissements'][0] ?? null;

        // 🚨 RÈGLE 1 : L'entreprise légale est-elle radiée ?
        if ('C' === $etatAdministratif) {
            return new SirenResult(false, sprintf('L\'entreprise %s est officiellement radiée (Cessation)', $name ?? $siret), $etatAdministratif);
        }

        // 🚨 RÈGLE 2 : L'entreprise est-elle une "coquille vide" (Le cas LOU LOU SARL) ?
        if (0 === $nombreEtablissementsOuverts) {
            return new SirenResult(false, sprintf('L\'entreprise %s est inactive (Aucun établissement ouvert).', $name ?? $siret), $etatAdministratif);
        }

        // 🚨 RÈGLE 3 : Si on vérifie un SIRET précis, ce SIRET spécifique est-il fermé ?
        // (Parfois l'entreprise est active, mais l'adresse renseignée par l'utilisateur est une ancienne agence fermée)
        if (isset($matchingEtablissements)) {
            $etablissements = $entreprise[0]['matching_etablissements'][0];
            if ('F' === $etablissements) {
                return new SirenResult(false, sprintf('Le SIRET fourni pour %s correspond à un établissement fermé.', $name ?? $siret), $etatAdministratif);
            }
        }

        return new SirenResult(true, 'Entreprise et établissement actifs.', $etatAdministratif);
    }
}
