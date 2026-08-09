<?php

declare(strict_types=1);

namespace App\Application\Security\DTO;

final readonly class GeoIpResult
{
    public function __construct(
        public string $ipAddress,
        public ?string $countryCode,
        public ?string $countryName,
        public ?string $city,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'ip_address' => $this->ipAddress,
            'country_code' => $this->countryCode ?? 'XX',
            'country_name' => $this->countryName ?? 'Inconnu',
            'city' => $this->city ?? 'Inconnue',
        ];
    }
}
