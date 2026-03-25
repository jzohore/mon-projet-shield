<?php

namespace App\Infrastructure\Service;

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
    ) {}

    /**
     * @param string $searchQuery
     * @return array<array-key, mixed>
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
}
