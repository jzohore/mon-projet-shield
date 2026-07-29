<?php

declare(strict_types=1);

namespace App\Infrastructure\DocuSeal;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class DocuSealClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $docusealApiKey,      // Injecté via bind dans services.yaml
        private string $docusealBaseUrl,
        private int|string $derTemplateId,
    ) {
    }

    /**
     * @return array<string, string>
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function createSignatureRequest(string $clientEmail, string $clientName): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->docusealBaseUrl . '/api/submissions', [
                'headers' => [
                    'X-Auth-Token' => $this->docusealApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'template_id' => (int) $this->derTemplateId,
                    'send_email' => false,
                    'submitters' => [
                        [
                            // ⚠️ ATTENTION : Ce rôle DOIT correspondre EXACTEMENT
                            // au nom du signataire dans ton template DocuSeal.
                            'role' => 'Client',
                            'email' => $clientEmail,
                            'name' => $clientName,
                        ],
                    ],
                ],
            ]);

            // 🪄 toArray(false) permet de lire le JSON même si DocuSeal renvoie une erreur 400
            $data = $response->toArray(false);
            // Si DocuSeal a refusé la requête (ex: template introuvable, mauvais rôle)
            if ($response->getStatusCode() >= 400) {
                throw new \RuntimeException('L\'API DocuSeal a refusé la requête : ' . json_encode($data));
            }

            if (isset($data[0]['embed_src']) || isset($data[0]['url'])) {
                $signatureUrl = $data[0]['embed_src'] ?? $data[0]['url'] ?? null;
            }
            // Cas 2 : API Cloud (Retourne un dossier contenant des signataires)
            elseif (isset($data[0]['submitters'][0])) {
                $signatureUrl = $data[0]['submitters'][0]['embed_src'] ?? $data[0]['submitters'][0]['url'] ?? null;
            }
            // Cas 3 : Ancienne API (Objet direct)
            elseif (isset($data['submitters'][0])) {
                $signatureUrl = $data['submitters'][0]['embed_src'] ?? $data['submitters'][0]['url'] ?? null;
            } else {
                $signatureUrl = null;
            }

            if (!$signatureUrl) {
                throw new \RuntimeException('Structure inattendue. Voici la réponse : ' . json_encode($data));
            }

            $submissionId = $data[0]['id'] ?? null;
            $signatureUrl = $data[0]['embed_src'] ?? $data[0]['url'] ?? null;

            return [
                'id' => $submissionId,
                'url' => $signatureUrl,
            ];
        } catch (\Exception $e) {
            $this->logger->critical('Échec API DocuSeal', [
                'error' => $e->getMessage(),
            ]);

            // On remonte la vraie erreur à ton interface pour pouvoir la lire
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}

// 'Impossible de générer le document de signature pour le moment.'
