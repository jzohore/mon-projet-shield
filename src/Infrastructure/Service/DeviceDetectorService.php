<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Application\Device\DTO\Request\CreateDeviceRequest;
use App\Application\Device\UseCase\CreateDeviceUseCase;
use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\AbstractBotParser;
use DeviceDetector\Parser\AbstractParser;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\Model\City;
use MaxMind\Db\Reader\InvalidDatabaseException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DeviceDetectorService
{
    private ?Reader $cityDbReader = null;

    public function __construct(
        private readonly CreateDeviceUseCase   $createDevice,
        private readonly ParameterBagInterface $parameterBag,
        private readonly RequestStack          $requestStack,
    ) {}

    /**
     * Initialise le Reader GeoIP de manière lazy (une seule fois).
     * @throws InvalidDatabaseException
     */
    private function getReader(): Reader
    {
        if (null === $this->cityDbReader) {
            $path = $this->parameterBag->get('GeoIP2_file');
            if (!is_string($path)) {
                throw new \RuntimeException('Le paramètre GeoIP2_file doit être une chaîne de caractères.');
            }
            $this->cityDbReader = new Reader($path);
        }

        return $this->cityDbReader;
    }

    /**
     * @throws \Exception
     */
    public function getDeviceDetector(Request $request): DeviceDetector
    {
        AbstractBotParser::setVersionTruncation(AbstractParser::VERSION_TRUNCATION_NONE);

        /** @var string $userAgent */
        $userAgent = $request->headers->get('User-Agent', '');
        $clientHints = ClientHints::factory($request->server->all());

        return new DeviceDetector($userAgent, $clientHints);
    }

    /**
     * @throws InvalidDatabaseException
     */
    public function getCityData(string $ip): ?City
    {
        try {
            return $this->getReader()->city($ip);
        } catch (AddressNotFoundException) {
            return null;
        }
    }

    /**
     * @throws InvalidDatabaseException
     */
    public function createDeviceDetector(?string $userSlugId): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        //$ip = $request->getClientIp() ?? '2a01:cb00:8b5:5700:e46b:ac5d:48f3:ad81';
        $ip = '2a01:cb00:8b5:5700:e46b:ac5d:48f3:ad81';
        $session = $request->hasSession() ? $request->getSession() : null;

        $device = $this->getDeviceDetector($request);
        $device->parse();

        $cityData = $this->getCityData($ip);

        $deviceDTO = new CreateDeviceRequest();
        $deviceDTO->userSlugId = $userSlugId;
        $deviceDTO->addressIp = $ip;
        $deviceDTO->sessionId = $session?->getId();

        // Données Device
        $deviceDTO->clientDeviceName = $device->getDeviceName();
        $deviceDTO->clientBrandName = $device->getBrandName();
        $deviceDTO->clientIsBrowser = $device->isBrowser();
        $deviceDTO->clientIsSmartphone = $device->isSmartphone();

        /** @var array<string, mixed> $os */
        $os = $device->getOs();
        /** @var array<string, mixed> $client */
        $client = $device->getClient();

        // Correction PHPStan : Conversion des tableaux associatifs en listes de valeurs
        $deviceDTO->clientOs = $os;
        $deviceDTO->clientInfo = $client;

        // Données Géo (MaxMind)
        if ($cityData) {
            $deviceDTO->countryIsoCode = $cityData->country->isoCode;
            $deviceDTO->postalCode = $cityData->postal->code;
            $deviceDTO->cityName = $cityData->city->name;
            $deviceDTO->longitude = $cityData->location->longitude;
            $deviceDTO->latitude = $cityData->location->latitude;
        }

        ($this->createDevice)($deviceDTO);
    }
}
