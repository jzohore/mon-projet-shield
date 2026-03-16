<?php

namespace App\Infrastructure\Service;

use App\Application\Device\DTO\Request\CreateDeviceRequest;
use App\Application\Device\UseCase\CreateDeviceUseCase;
use App\Domain\User\Repository\UserRepositoryInterface;
use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\AbstractBotParser;
use DeviceDetector\Parser\AbstractParser;
use GeoIp2\Database\Reader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\Model\City;
use MaxMind\Db\Reader\InvalidDatabaseException;

readonly class DeviceDetectorService
{
    public function __construct(
        private CreateDeviceUseCase     $createDevice,
        private ParameterBagInterface   $parameterBag,
        private RequestStack            $requestStack,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function deviceDetector(): DeviceDetector
    {
        AbstractBotParser::setVersionTruncation(AbstractParser::VERSION_TRUNCATION_NONE);

        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $clientHints = ClientHints::factory($_SERVER);

        return new DeviceDetector($userAgent, $clientHints);
    }

    public function getAddressIp(): ?string
    {
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        // IP derrière un proxy
        elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        // Sinon : IP normale
        else {
            return $_SERVER['REMOTE_ADDR'] ?? '';
        }
    }

    /**
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    public function getCityReader(): City
    {
        $cityDbReader = new Reader($this->parameterBag->get('GeoIP2_file'));
        // $addressIp = $this->getAddressIp();
        $addressIp = '2a01:cb00:8b5:5700:e46b:ac5d:48f3:ad81';

        return $cityDbReader->city(ipAddress: $addressIp);
    }

    /**
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    public function getCountryIsoCode(): ?string
    {
        return $this->getCityReader()->country->isoCode;
    }

    /**
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    public function getLongitude(): ?float
    {
        return $this->getCityReader()->location->longitude;
    }

    /**
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    public function getLatitude(): ?float
    {
        return $this->getCityReader()->location->latitude;
    }

    /**
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    public function getCityName(): ?string
    {
        return $this->getCityReader()->city->name;
    }

    /**
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    public function getPostalCode(): ?string
    {
        return $this->getCityReader()->postal->code;
    }

    /**
     * @throws InvalidDatabaseException
     * @throws AddressNotFoundException
     */
    public function createDeviceDetector(string $userSlugId): void
    {
        $currentSessionId = $this->requestStack->getCurrentRequest()->getSession()->getId();
        $device = $this->deviceDetector();
        $device->parse();

        $deviceDTO = new CreateDeviceRequest();
        $deviceDTO->addressIp = $this->getAddressIp();
        $deviceDTO->clientDeviceName = $device->getDeviceName();
        $deviceDTO->clientBrandName = $device->getBrandName();
        $deviceDTO->clientIsBrowser = $device->isBrowser();
        $deviceDTO->clientIsSmartphone = $device->isSmartphone();
        $deviceDTO->clientOs = $device->getOs();
        $deviceDTO->clientInfo = $device->getClient();
        $deviceDTO->sessionId = $currentSessionId;
        $deviceDTO->countryIsoCode = $this->getCountryIsoCode();
        $deviceDTO->postalCode = $this->getPostalCode();
        $deviceDTO->cityName = $this->getCityName();
        $deviceDTO->longitude = $this->getLongitude();
        $deviceDTO->latitude = $this->getLatitude();
        $deviceDTO->userSlugId = $userSlugId;

        ($this->createDevice)($deviceDTO);
    }
}
