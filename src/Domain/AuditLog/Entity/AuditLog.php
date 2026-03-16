<?php

namespace App\Domain\AuditLog\Entity;

use App\Domain\AuditLog\Enum\AuditEventType;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'audit_logs')]
#[ORM\Index(columns: ['event_name'])]
#[ORM\Index(columns: ['resource_id'])]
class AuditLog
{
    use GenerateSlugPrefixedTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null {
        get => $this->id;
    }

    #[ORM\Column(type: Types::STRING, length: 180, enumType: AuditEventType::class)]
    public ?AuditEventType $eventName = null {
        get => $this->eventName;
    }

    #[ORM\Column(type: Types::STRING, length: 180)]
    public ?string $resourceId = null {
        get => $this->resourceId;
    }

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public ?string $slugId = null {
        get => $this->slugId;
    }

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON)]
    public ?array $payload = null {
        get => $this->payload ?? [];
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $occurredAt {
        get => $this->occurredAt;
    }

    /**
     * @param AuditEventType $eventName
     * @param string $resourceId
     * @param array<string, mixed> $payload
     * @throws \DateMalformedStringException
     */
    public function __construct(
        AuditEventType $eventName,
        string $resourceId,
        array $payload = []
    ) {
        $this->eventName = $eventName;
        $this->resourceId = strtolower(trim($resourceId));
        $this->payload = $payload;

        // Un préfixe logique pour l'audit
        $this->slugId = $this->generate_ulid_prefixed('aud_');

        $this->occurredAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
