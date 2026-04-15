<?php

namespace App\Infrastructure\Screening\Service;

use App\Domain\Port\OpenSanctionsClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

readonly class OpenSanctionsClient implements OpenSanctionsClientInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $openSanctionsApiKey,
        private LoggerInterface $logger
    ) {}

    public function search(string $name, string $schema = 'Person'): array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.opensanctions.org/search/default', [
                'query' => ['q' => $name, 'schema' => $schema],
                'headers' => ['Authorization' => 'Api-Key ' . $this->openSanctionsApiKey],
            ]);

            $data = $response->toArray();
            $alerts = [];

            if ($data['total']['value'] > 0) {
                foreach ($data['results'] as $result) {
                    $props = $result['properties'];

                    $countries = array_unique(array_merge($props['country'] ?? [], $props['citizenship'] ?? []));

                    $alerts[] = [
                        'id' => $result['id'],
                        'name' => $result['caption'],
                        'schema' => $result['schema'],
                        'topics' => $props['topics'] ?? [],

                        // On récupère TOUTES les positions, pas juste la première
                        'positions' => $props['position'] ?? [],

                        // Nouveaux champs cruciaux pour le LCB-FT
                        'birth_dates' => $props['birthDate'] ?? [],
                        'incorporation_dates' => $props['incorporationDate'] ?? [],
                        'notes' => array_merge($props['notes'] ?? [], $props['description'] ?? []),
                        'countries' => $countries,
                        'registration_numbers' => array_merge(
                            $props['leiCode'] ?? [],
                            $props['registrationNumber'] ?? [],
                            $props['ogrnCode'] ?? [] // Spécifique Russie, très utile
                        ),
                        'aliases' => $props['alias'] ?? [],
                        'datasets' => $result['datasets'] ?? [], // Ex: ["wd_peps", "fr_assemblee"]

                        'raw_data' => $result,
                    ];
                }
            }

            return [
                'total_matches' => $data['total']['value'],
                'alerts' => $alerts,
            ];
        } catch (ClientExceptionInterface $e) {
            $statusCode = $e->getResponse()->getStatusCode();

            // Si c'est l'erreur "Rate Limit Exceeded"
            if ($statusCode === 429) {
                // 1. On logge l'erreur en critique pour que tu reçoives une alerte (Slack/Email)
                $this->logger->critical('🚨 OPEN SANCTIONS RATE LIMIT ATTEINT ! Le service est bloqué.');

                // 2. On lève une exception métier "propre" que le contrôleur pourra afficher au client
                throw new \DomainException('Le service de vérification (Sanctions) est saturé pour le moment. Veuillez réessayer dans quelques minutes.');
            }

            // Pour toute autre erreur API (ex: 401 Unauthorized), on la laisse remonter
            throw $e;
        }
    }
}
