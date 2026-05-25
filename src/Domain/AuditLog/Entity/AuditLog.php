<?php

namespace App\Domain\AuditLog\Entity;

use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\Workspace\Entity\Workspace;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

use function Symfony\Component\Clock\now;

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

    #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'auditLogs')]
    #[ORM\JoinColumn(nullable: true)]
    public private(set) ?Workspace $workspace = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public private(set) string $actor;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public private(set) string $slugId;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    public private(set) array $payload;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $occurredAt;

    /**
     * @param AuditEventType $eventName
     * @param array<string, mixed> $payload
     * @param string $actor
     * @param Workspace|null $workspace
     * @throws Exception
     */
    protected function __construct(
        AuditEventType $eventName,
        array $payload,
        string $actor,
        ?Workspace $workspace = null,
    ) {
        $this->workspace = $workspace;
        $this->actor = $actor;
        $this->eventName = $eventName;
        $this->payload = $payload;
        $this->slugId = $this->generate_ulid_prefixed('aud_');
        $this->occurredAt = now();
    }

    /**
     * @param AuditEventType $eventName
     * @param string $actor
     * @param Workspace|null $workspace
     * @return AuditLog
     * @throws Exception
     * @param array<string, mixed> $payload
     */
    public static function initiate(AuditEventType $eventName, array $payload, string $actor, ?Workspace $workspace = null): self
    {
        return new self($eventName, $payload, $actor, $workspace);
    }
}
