<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Application\Security\DTO\GeoIpResult;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class IpGeocoder
{
    public function __construct(
        private RequestStack $requestStack,
        private HttpClientInterface $httpClient,
        #[Autowire('%kernel.environment%')]
        private string $appEnv,
    ) {
    }

    /**
     * Géolocalise une adresse IP spécifique ou extrait l'emplacement de la requête courante.
     */
    public function geolocate(?string $ipAddress = null): GeoIpResult
    {
        $request = $this->requestStack->getCurrentRequest();

        // 1. Extraction de l'IP cible
        $targetIp = $ipAddress ?? $request?->getClientIp() ?? '127.0.0.1';

        // 2. Gestion des environnements de développement locaux
        if ($this->isLocalIp($targetIp)) {
            if ('dev' === $this->appEnv) {
                // IP de simulation française pour les tests locaux (Orange SAS)
                $targetIp = '185.24.184.1';
            } else {
                return new GeoIpResult($targetIp, 'FR', 'France', 'Local Dev');
            }
        }

        // 3. Extraction prioritaire depuis les en-têtes du Reverse Proxy (Staging / Prod)
        if ($request instanceof \Symfony\Component\HttpFoundation\Request) {
            $countryCode = $request->headers->get('CF-IPCountry') ?? $request->headers->get('X-GeoIP-Country');
            $city = $request->headers->get('X-GeoIP-City');

            if (null !== $countryCode) {
                return new GeoIpResult(
                    ipAddress: $targetIp,
                    countryCode: strtoupper($countryCode),
                    countryName: null,
                    city: $city
                );
            }
        }

        // 4. Fallback HTTP gratuit en dev/test sans fichier local
        try {
            $response = $this->httpClient->request('GET', "http://ip-api.com/json/{$targetIp}?fields=status,country,countryCode,city", [
                'timeout' => 2.0,
            ]);

            $data = $response->toArray();

            if (($data['status'] ?? 'fail') === 'success') {
                return new GeoIpResult(
                    ipAddress: $targetIp,
                    countryCode: $data['countryCode'] ?? null,
                    countryName: $data['country'] ?? null,
                    city: $data['city'] ?? null
                );
            }
        } catch (\Throwable) {
            // Résilience SecOps : la défaillance de géolocalisation ne doit pas bloquer les workflows applicatifs
        }

        return new GeoIpResult($targetIp, null, null, null);
    }

    private function isLocalIp(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1', 'localhost'], true)
            || str_starts_with($ip, '192.168.')
            || str_starts_with($ip, '10.');
    }
}
