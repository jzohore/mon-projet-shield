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
 * suivi par plusieurs CGP). Ce qui est propre à chaque cabinet — la relation
 * est en attente / active / clôturée, depuis quand, pour quel motif — vit ici,
 * pas sur le compte. `Client::$isActif` n'est qu'un cache « actif pour au moins
 * un cabinet », entretenu par ces transitions.
 *
 * Cycle : `PENDING` (le cabinet a ajouté le client mais la relation n'est pas
 * confirmée — tant qu'aucun DER n'a été accusé, le cabinet ne voit QUE le nom
 * qu'il a saisi, jamais les coordonnées maîtres du compte) → `ACTIVE`
 * (`confirmedAt` posé au 1er accusé de réception de DER) → `ENDED` (`endedAt`).
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
    public private(set) ?\DateTimeImmutable $confirmedAt = null;

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
        /**
         * Identité saisie par CE cabinet à l'ajout. Seule chose visible tant que la
         * relation n'est pas confirmée — jamais les champs maîtres du compte, qui
         * peuvent appartenir à un autre cabinet.
         */
        #[ORM\Column(type: Types::STRING, length: 100)]
        public private(set) string $invitedFirstName,
        #[ORM\Column(type: Types::STRING, length: 100)]
        public private(set) string $invitedLastName,
    ) {
        $this->startedAt = now();
    }

    public static function start(Client $client, Workspace $workspace, string $invitedFirstName, string $invitedLastName): self
    {
        return new self($client, $workspace, $invitedFirstName, $invitedLastName);
    }

    /** Relation confirmée et non clôturée : le client compte comme actif pour ce cabinet. */
    public function isActive(): bool
    {
        return $this->confirmedAt instanceof \DateTimeImmutable && !$this->endedAt instanceof \DateTimeImmutable;
    }

    /** Ajoutée mais pas encore confirmée (aucun DER accusé) : coordonnées maîtres masquées. */
    public function isPending(): bool
    {
        return !$this->confirmedAt instanceof \DateTimeImmutable && !$this->endedAt instanceof \DateTimeImmutable;
    }

    public function isEnded(): bool
    {
        return $this->endedAt instanceof \DateTimeImmutable;
    }

    public function invitedFullName(): string
    {
        return trim(sprintf('%s %s', ucfirst(mb_strtolower($this->invitedFirstName)), mb_strtoupper($this->invitedLastName)));
    }

    /**
     * Confirme la relation (1er accusé de réception de DER). Idempotent ; sans
     * effet sur une relation déjà clôturée.
     */
    public function confirm(): void
    {
        if ($this->confirmedAt instanceof \DateTimeImmutable || $this->endedAt instanceof \DateTimeImmutable) {
            return;
        }

        $this->confirmedAt = now();
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
     * Rouvre la relation (nouvelle mise en relation avec ce cabinet). Repart en
     * attente de confirmation.
     */
    public function reopen(string $invitedFirstName, string $invitedLastName): void
    {
        $this->invitedFirstName = $invitedFirstName;
        $this->invitedLastName = $invitedLastName;
        $this->startedAt = now();
        $this->confirmedAt = null;
        $this->endedAt = null;
        $this->endReason = null;
    }
}
