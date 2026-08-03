<?php

declare(strict_types=1);

namespace App\Application\Device\DTO\Request;

class CreateDeviceRequest
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $clientInfo = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $clientOs = null;

    public ?string $clientDeviceName = null;

    public ?string $clientBrandName = null;

    public ?bool $clientIsBrowser = null;

    public ?bool $clientIsSmartphone = null;

    public ?string $addressIp = null;

    public ?string $userSlugId = null;

    public ?string $sessionId = null;

    public ?string $countryIsoCode = null;

    public ?string $cityName = null;

    public ?string $postalCode = null;

    public ?float $latitude = null;

    public ?float $longitude = null;
}
