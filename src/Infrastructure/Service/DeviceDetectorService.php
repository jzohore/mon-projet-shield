<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Application\Security\DTO\CreateDeviceRequest;
use App\Application\Security\UseCase\CreateDeviceUseCase;
use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\AbstractBotParser;
use DeviceDetector\Parser\AbstractParser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class DeviceDetectorService
{
    public function __construct(
        private CreateDeviceUseCase $createDevice,
        private RequestStack $requestStack,
        private IpGeocoder $ipGeocoder,
    ) {
    }

    public function trackCurrentDevice(string $userSlugId): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return;
        }

        $ip = $request->getClientIp() ?? '127.0.0.1';
        $session = $request->hasSession() ? $request->getSession() : null;
        $sessionId = $session?->getId() ?? md5($ip . ($request->headers->get('User-Agent') ?? ''));

        // 1. Analyse User-Agent / ClientHints
        AbstractBotParser::setVersionTruncation(AbstractParser::VERSION_TRUNCATION_NONE);
        $userAgent = (string) $request->headers->get('User-Agent', '');
        $clientHints = ClientHints::factory($request->server->all());

        $dd = new DeviceDetector($userAgent, $clientHints);
        $dd->parse();

        // 2. Géolocalisation via le service hybride KYSURE
        $geoResult = $this->ipGeocoder->geolocate($ip);

        /** @var array<string, mixed> $os */
        $os = $dd->getOs() ?: [];
        /** @var array<string, mixed> $client */
        $client = $dd->getClient() ?: [];

        // 3. Assemblage du DTO Immutable
        $deviceDTO = new CreateDeviceRequest(
            userSlugId: $userSlugId,
            addressIp: $ip,
            sessionId: $sessionId,
            clientOs: $os,
            clientInfo: $client,
            clientDeviceName: $dd->getDeviceName() ?: 'Desktop',
            clientBrandName: $dd->getBrandName() ?: 'Generic',
            clientIsBrowser: $dd->isBrowser(),
            clientIsSmartphone: $dd->isSmartphone(),
            countryIsoCode: $geoResult->countryCode,
            cityName: $geoResult->city,
        );

        // 4. Exécution du UseCase
        ($this->createDevice)($deviceDTO);
    }
}
