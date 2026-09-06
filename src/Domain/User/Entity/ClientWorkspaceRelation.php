<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use App\Domain\Compliance\Enum\RelationshipEndReason;
use App\Domain\Workspace\Entity\Workspace;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;

use function Symfony\Component\Clock\now;

use Symfony\Component\Uid\Uuid;

/**
 * État de la relation d'affaires entre UN client et UN cabinet.
 *
 * Le compte {@see Client} est mutualisé entre cabinets (un particulier peut être
 * suivi par plusieurs CGP). Ce qui est propre à chaque cabinet — la relation est
 * active ou clôturée, depuis quand, pour quel motif — vit ici, pas sur le
 * compte. `Client::$isActif` n'est qu'un cache « actif pour au moins un
 * cabinet », entretenu par ces transitions.
 */
#[ORM\Entity]
#[ORM\Table(name: 'client_workspace_relations')]
#[ORM\UniqueConstraint(name: 'uniq_client_workspace_rel', columns: ['client_id', 'workspace_id'])]
#[ORM\Index(name: 'idx_cwr_client', columns: ['client_id'])]
#[ORM\Index(name: 'idx_cwr_workspace', columns: ['workspace_id'])]
class ClientWorkspaceRelation
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public private(set) ?Uuid $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $startedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $endedAt = null;

    #[ORM\Column(type: Types::STRING, length: 40, nullable: true, enumType: RelationshipEndReason::class)]
    public private(set) ?RelationshipEndReason $endReason = null;

    private function __construct(
        #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'relations')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        public private(set) Client $client,
        #[ORM\ManyToOne(targetEntity: Workspace::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        public private(set) Workspace $workspace,
    ) {
        $this->startedAt = now();
    }

    public static function start(Client $client, Workspace $workspace): self
    {
        return new self($client, $workspace);
    }

    public function isActive(): bool
    {
        return !$this->endedAt instanceof \DateTimeImmutable;
    }

    /**
     * Clôture la relation pour ce cabinet. Idempotent : rejeu → no-op.
     */
    public function end(RelationshipEndReason $reason): void
    {
        if ($this->endedAt instanceof \DateTimeImmutable) {
            return;
        }

        $this->endedAt = now();
        $this->endReason = $reason;
    }

    /**
     * Rouvre la relation (nouvelle mise en relation avec ce cabinet).
     */
    public function reopen(): void
    {
        $this->endedAt = null;
        $this->endReason = null;
        $this->startedAt = now();
    }
}
