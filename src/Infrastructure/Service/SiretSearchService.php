<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Application\ExternalAPI\Siren\SirenResult;
use App\Domain\Workspace\Exception\CompanyRegistryUnavailableException;
use App\Domain\Workspace\Exception\InvalidSiretException;
use App\Domain\Workspace\Gateway\SiretCheckerInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class SiretSearchService implements SiretCheckerInterface
{
    public function __construct(
        private HttpClientInterface $client,
    ) {
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws DecodingExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function search(string $searchQuery): array
    {
        try {
            $response = $this->client->request('GET', 'https://recherche-entreprises.api.gouv.fr/search', [
                'query' => [
                    'q' => trim($searchQuery),
                    'page' => 1,
                    'per_page' => 1, // Frugalité : on n'a besoin que du premier résultat exact
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            $data = $response->toArray();

            return $data['results'] ?? [];
        } catch (HttpExceptionInterface $e) {
            // ACL : On masque l'erreur HTTP derrière une exception métier explicite
            throw new CompanyRegistryUnavailableException(message: 'Impossible de joindre le registre officiel des entreprises (INSEE).', code: $e->getCode(), previous: $e);
        }
    }

    /**
     * @throws DecodingExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function verifyStatus(string $siret, ?string $name = null): SirenResult
    {
        $results = $this->search($siret);

        // 🚨 SÉCURITÉ : L'API a répondu 200 OK, mais aucun résultat ne correspond
        if ([] === $results) {
            throw new InvalidSiretException(sprintf('Le numéro SIRET/SIREN "%s" n\'est pas reconnu par l\'INSEE.', $siret));
        }

        $entreprise = $results[0];
        $etatAdministratif = $entreprise['etat_administratif'] ?? null;
        $nombreEtablissementsOuverts = $entreprise['nombre_etablissements_ouverts'] ?? 0;

        // 🚨 RÈGLE 1 : L'entreprise légale est-elle radiée ?
        if ('C' === $etatAdministratif) {
            return new SirenResult(
                isActive: false,
                message: sprintf('L\'entreprise %s est officiellement radiée (Cessation).', $name ?? $siret),
                etatAdministratif: $etatAdministratif
            );
        }

        // 🚨 RÈGLE 2 : L'entreprise est-elle une "coquille vide" ?
        if (0 === $nombreEtablissementsOuverts) {
            return new SirenResult(
                isActive: false,
                message: sprintf('L\'entreprise %s est inactive (Aucun établissement ouvert).', $name ?? $siret),
                etatAdministratif: $etatAdministratif
            );
        }

        // 🚨 RÈGLE 3 : Le SIRET précis fourni correspond-il à un établissement fermé ?
        $matchingEtablissements = $entreprise['matching_etablissements'] ?? [];
        if (!empty($matchingEtablissements) && isset($matchingEtablissements[0])) {
            // Correction du bug : on cible bien la propriété d'état du sous-tableau
            $etatEtablissement = $matchingEtablissements[0]['etat_administratif'] ?? null;

            if ('F' === $etatEtablissement) {
                return new SirenResult(
                    isActive: false,
                    message: sprintf('Le SIRET fourni pour %s correspond à un établissement fermé.', $name ?? $siret),
                    etatAdministratif: $etatAdministratif
                );
            }
        }

        return new SirenResult(
            isActive: true,
            message: 'Entreprise et établissement actifs.',
            etatAdministratif: $etatAdministratif
        );
    }
}
