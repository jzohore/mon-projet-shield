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

/**
 * Journal d'audit AMF/LCB-FT. Immuable par conception : une fois écrit, un
 * enregistrement ne doit ni être modifié ni supprimé à l'unité. Toutes les
 * colonnes sont `updatable: false` (Doctrine ne les inclura jamais dans un
 * UPDATE) et les propriétés sont `private(set)` (pas de réassignation en PHP).
 * La purge éventuelle se fait par lot, à échéance légale, jamais sur demande.
 */
#[ORM\Entity]
#[ORM\Table(name: 'audit_logs')]
#[ORM\Index(columns: ['event_name'])]
class AuditLog
{
    use GenerateSlugPrefixedTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true, updatable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public private(set) ?Uuid $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true, updatable: false)]
    public private(set) string $slugId;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, updatable: false)]
    public private(set) \DateTimeImmutable $occurredAt;

    /**
     * @param array<string, mixed> $payload
     *
     * @throws \Exception
     */
    protected function __construct(
        #[ORM\Column(type: Types::STRING, length: 180, updatable: false, enumType: AuditEventType::class)]
        public private(set) AuditEventType $eventName,
        #[ORM\Column(type: Types::JSON, updatable: false)]
        public private(set) array $payload,
        #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'auditLogs')]
        #[ORM\JoinColumn(nullable: true)]
        public private(set) ?Workspace $workspace = null,
    ) {
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
