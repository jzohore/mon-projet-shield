<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Entity;

use App\Domain\Workspace\Enum\LegalDemandStatus;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;

use function Symfony\Component\Clock\now;

use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'legal_update_demands')]
class LegalUpdateDemand
{
    use GenerateSlugPrefixedTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null {
        get => $this->id;
    }

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public private(set) string $slugId;

    // --- Les Preuves (Chemins de stockage S3 ou local) ---
    #[ORM\Column(type: 'string', nullable: true)]
    public private(set) ?string $kbisDocumentPath = null;

    #[ORM\Column(type: 'string', nullable: true)]
    public private(set) ?string $identityDocumentPath = null;

    // --- Statut et Audit ---
    #[ORM\Column(type: 'string', enumType: LegalDemandStatus::class)]
    public private(set) LegalDemandStatus $status = LegalDemandStatus::CREATED;

    #[ORM\Column(type: 'text', nullable: true)]
    public private(set) ?string $rejectionReason = null;

    #[ORM\Column(type: 'datetime_immutable')]
    public private(set) \DateTimeImmutable $submittedAt;
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public private(set) ?\DateTimeImmutable $updatedAt = null;

    /**
     * @throws \Exception
     */
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'legalUpdateDemands')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        public private(set) Workspace $workspace,
        #[ORM\Column(type: 'string', length: 14)]
        public private(set) string $requestedSiret,
        #[ORM\Column(type: 'string', length: 9)]
        public private(set) string $requestedSiren,
        #[ORM\Column(type: 'string')]
        public private(set) string $requestedName,
    ) {
        $this->id = Uuid::v4();
        $this->submittedAt = now();
        $this->slugId = $this->generate_ulid_prefixed('lgr_upd_dem_');
    }

    /**
     * @throws \Exception
     */
    public static function createFromDemand(Workspace $workspace, string $requestedSiret, string $requestedSiren, string $requestedName): self
    {
        return new self(
            $workspace,
            $requestedSiret,
            $requestedSiren,
            $requestedName,
        );
    }

    public function update(string $kbisDocumentPath, string $identityDocumentPath): void
    {
        $this->kbisDocumentPath = $kbisDocumentPath;
        $this->identityDocumentPath = $identityDocumentPath;
        $this->updatedAt = now();
    }

    public function approve(): void
    {
        $this->status = LegalDemandStatus::ACCEPTED;
    }

    public function reject(string $reason): void
    {
        $this->status = LegalDemandStatus::REJECTED;
        $this->rejectionReason = $reason;
    }
}
