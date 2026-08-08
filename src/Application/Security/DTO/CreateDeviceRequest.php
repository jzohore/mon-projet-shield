<?php

declare(strict_types=1);

namespace App\Application\Security\DTO;

use Webmozart\Assert\Assert;

/**
 * DTO immutable pour la création et la traçabilité d'un appareil connecté.
 */
final readonly class CreateDeviceRequest
{
    /**
     * @param array<string, mixed> $clientOs
     * @param array<string, mixed> $clientInfo
     */
    public function __construct(
        public string $userSlugId,
        public string $addressIp,
        public string $sessionId,
        public array $clientOs,
        public array $clientInfo,
        public ?string $clientDeviceName = null,
        public ?string $clientBrandName = null,
        public bool $clientIsBrowser = true,
        public bool $clientIsSmartphone = false,
        public ?string $countryIsoCode = null,
        public ?string $cityName = null,
        public ?string $postalCode = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {
        Assert::notEmpty($userSlugId, 'Le slug utilisateur est requis.');
        Assert::notEmpty($addressIp, 'L\'adresse IP est requise.');
        Assert::notEmpty($sessionId, 'L\'ID de session est requis.');
    }
}
