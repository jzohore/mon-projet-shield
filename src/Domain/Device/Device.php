<?php

namespace App\Domain\Device;

use App\Domain\User\Entity\User;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'user_devices')]
class Device
{
    // N'oublie pas le trait si tu en as besoin
    use GenerateSlugPrefixedTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null {
        get => $this->id;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'devices')]
    #[ORM\JoinColumn(name: 'owner_id', nullable: false, onDelete: 'CASCADE')]
    public ?User $owner = null {
        get => $this->owner;
        // Pas de setter, le device appartient à ce user pour toujours
    }

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    public ?array $clientInfo {
        get => $this->clientInfo;
        set => $this->clientInfo = $value;
    }

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    public ?array $clientOs {
        get => $this->clientOs;
        set => $this->clientOs = $value;
    }

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public ?string $clientDeviceName {
        get => $this->clientDeviceName;
        set => $this->clientDeviceName = $value;
    }

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public ?string $clientBrandName {
        get => $this->clientBrandName;
        set => $this->clientBrandName = $value;
    }

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    public ?bool $clientIsBrowser {
        get => $this->clientIsBrowser;
        set => $this->clientIsBrowser = $value;
    }

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    public ?bool $clientIsSmartphone {
        get => $this->clientIsSmartphone;
        set => $this->clientIsSmartphone = $value;
    }

    #[ORM\Column(type: Types::STRING, length: 45, nullable: true)] // 45 chars max pour IPv6
    public ?string $addressIp {
        get => $this->addressIp;
        set => $this->addressIp = $value;
    }

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public ?string $sessionId {
        get => $this->sessionId;
        set => $this->sessionId = $value;
    }

    #[ORM\Column(type: Types::STRING, length: 2, nullable: true)] // Code ISO 2 lettres
    public ?string $countryIsoCode {
        get => $this->countryIsoCode;
        set => $this->countryIsoCode = $value;
    }

    #[ORM\Column(type: Types::STRING, length: 180, nullable: true)]
    public ?string $cityName {
        get => $this->cityName;
        set => $this->cityName = $value;
    }

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    public ?string $postalCode {
        get => $this->postalCode;
        set => $this->postalCode = $value;
    }

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    public ?float $latitude {
        get => $this->latitude;
        set => $this->latitude = $value;
    }

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    public ?float $longitude {
        get => $this->longitude;
        set => $this->longitude = $value;
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt {
        get => $this->createdAt;
    }

    /**
     * @param array<string, mixed>|null $clientInfo
     * @param array<string, mixed>|null $clientOs
     */
    public function __construct(
        User $owner,
        ?array $clientInfo = null,
        ?array $clientOs = null,
        ?string $clientDeviceName = null,
        ?string $clientBrandName = null,
        ?bool $clientIsBrowser = null,
        ?bool $clientIsSmartphone = null,
        ?string $addressIp = null,
        ?string $sessionId = null,
        ?string $countryIsoCode = null,
        ?string $cityName = null,
        ?string $postalCode = null,
        ?float $latitude = null,
        ?float $longitude = null,
    ) {
        $this->owner = $owner;
        $this->clientInfo = $clientInfo;
        $this->clientOs = $clientOs;
        $this->clientDeviceName = $clientDeviceName;
        $this->clientBrandName = $clientBrandName;
        $this->clientIsBrowser = $clientIsBrowser;
        $this->clientIsSmartphone = $clientIsSmartphone;
        $this->addressIp = $addressIp;
        $this->sessionId = $sessionId;
        $this->countryIsoCode = $countryIsoCode;
        $this->cityName = $cityName;
        $this->postalCode = $postalCode;
        $this->latitude = $latitude;
        $this->longitude = $longitude;

        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'))->setTime(0, 0, 0);
    }
}
