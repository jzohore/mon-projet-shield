<?php

declare(strict_types=1);

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

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: false, onDelete: 'CASCADE')]
    public ?User $owner = null {
        get => $this->owner;
        // Pas de setter, le device appartient à ce user pour toujours
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
        #[ORM\Column(type: Types::JSON, nullable: true)]
        public ?array $clientInfo = null {
            get => $this->clientInfo;
            set => $this->clientInfo = $value;
        },
        #[ORM\Column(type: Types::JSON, nullable: true)]
        public ?array $clientOs = null {
            get => $this->clientOs;
            set => $this->clientOs = $value;
        },
        #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
        public ?string $clientDeviceName = null {
            get => $this->clientDeviceName;
            set => $this->clientDeviceName = $value;
        },
        #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
        public ?string $clientBrandName = null {
            get => $this->clientBrandName;
            set => $this->clientBrandName = $value;
        },
        #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
        public ?bool $clientIsBrowser = null {
            get => $this->clientIsBrowser;
            set => $this->clientIsBrowser = $value;
        },
        #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
        public ?bool $clientIsSmartphone = null {
            get => $this->clientIsSmartphone;
            set => $this->clientIsSmartphone = $value;
        },
        #[ORM\Column(type: Types::STRING, length: 45, nullable: true)]
        public ?string $addressIp = null {
            get => $this->addressIp;
            set => $this->addressIp = $value;
        },
        #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
        public ?string $sessionId = null {
            get => $this->sessionId;
            set => $this->sessionId = $value;
        },
        #[ORM\Column(type: Types::STRING, length: 2, nullable: true)]
        public ?string $countryIsoCode = null {
            get => $this->countryIsoCode;
            set => $this->countryIsoCode = $value;
        },
        #[ORM\Column(type: Types::STRING, length: 180, nullable: true)]
        public ?string $cityName = null {
            get => $this->cityName;
            set => $this->cityName = $value;
        },
        #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
        public ?string $postalCode = null {
            get => $this->postalCode;
            set => $this->postalCode = $value;
        },
        #[ORM\Column(type: Types::FLOAT, nullable: true)]
        public ?float $latitude = null {
            get => $this->latitude;
            set => $this->latitude = $value;
        },
        #[ORM\Column(type: Types::FLOAT, nullable: true)]
        public ?float $longitude = null {
            get => $this->longitude;
            set => $this->longitude = $value;
        },
    ) {
        $this->owner = $owner;

        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'))->setTime(0, 0, 0);
    }
}
