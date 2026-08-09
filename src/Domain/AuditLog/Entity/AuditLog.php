<?php

declare(strict_types=1);

namespace App\Domain\AuditLog\Entity;

use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\Workspace\Entity\Workspace;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;

use function Symfony\Component\Clock\now;

use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'audit_logs')]
#[ORM\Index(columns: ['event_name'])]
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

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public private(set) string $slugId;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $occurredAt;

    /**
     * @param array<string, mixed> $payload
     *
     * @throws \Exception
     */
    protected function __construct(
        AuditEventType $eventName,
        #[ORM\Column(type: Types::JSON)]
        public private(set) array $payload,
        #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'auditLogs')]
        #[ORM\JoinColumn(nullable: true)]
        public private(set) ?Workspace $workspace = null,
    ) {
        $this->eventName = $eventName;
        $this->slugId = $this->generate_ulid_prefixed('aud_');
        $this->occurredAt = now();
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @throws \Exception
     */
    public static function initiate(AuditEventType $eventName, array $payload, ?Workspace $workspace = null): self
    {
        return new self($eventName, $payload, $workspace);
    }
}
