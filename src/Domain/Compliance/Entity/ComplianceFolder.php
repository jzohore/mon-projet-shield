<?php

namespace App\Domain\Compliance\Entity;

use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Enum\DiligenceLevel;
use App\Domain\Compliance\Enum\RiskLevel;
use App\Domain\Compliance\Exception\FolderStateException;
use App\Domain\Kyc\Enum\DocumentType;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity()]
#[ORM\Table(name: 'compliance_folders')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorMap(['individual' => IndividualFolder::class, 'business' => BusinessFolder::class])]
abstract class ComplianceFolder
{
    use GenerateSlugPrefixedTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public private(set) ?Uuid $id = null;

    #[ORM\Column(length: 20, unique: true)]
    public private(set) string $reference;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public private(set) string $slugId;

    #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'folders')]
    #[ORM\JoinColumn(nullable: false)]
    public private(set) Workspace $workspace;

    #[ORM\Column(type: 'string', enumType: ComplianceFolderStatus::class)]
    public private(set) ComplianceFolderStatus $status = ComplianceFolderStatus::DRAFT;

    /** @var Collection<int, ComplianceDocument> */
    #[ORM\OneToMany(targetEntity: ComplianceDocument::class, mappedBy: 'folder', cascade: ['persist', 'remove'])]
    public private(set) Collection $documents;

    /**
     * @var array<int|string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    public private(set) array $history = [];

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    public private(set) ?string $shareToken = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: RiskLevel::class)]
    public private(set) ?RiskLevel $riskLevel = null;

    #[ORM\Column(type: 'string', enumType: DiligenceLevel::class)]
    public private(set) DiligenceLevel $diligenceLevel = DiligenceLevel::STANDARD;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $nextReviewDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $submittedAt = null; // ✅ Ajouté

    #[ORM\Column(type: Types::BOOLEAN)]
    public private(set) bool $isCertified = false; // ✅ Ajouté

    #[ORM\ManyToOne(targetEntity: User::class)]
    public private(set) ?User $assignedReviewer = null;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    public private(set) ?array $metadata = [];

    #[ORM\Column]
    public private(set) bool $isConfidential = false;

    /** @var Collection<int, User> */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'compliance_folder_restricted_users')]
    public private(set) Collection $restrictedUsers;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $createdAt;

    /**
     * ✅ Constructeur protégé (DDD)
     */
    protected function __construct(Workspace $workspace, string $reference)
    {
        $this->workspace = $workspace;
        $this->reference = $reference;
        $this->createdAt = new \DateTimeImmutable();
        $this->documents = new ArrayCollection();
        $this->saveHistory('Dossier initié');
        $this->slugId = $this->generate_ulid_prefixed('comp_fol_');
        $this->restrictedUsers = new ArrayCollection();
    }

    public function addMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
    }

    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * ✅ Rendu du second argument optionnel pour plus de flexibilité
     */
    public function saveHistory(string $event, string $data = ''): void
    {
        $this->history[] = [
            'title' => $event,
            'description' => $data,
            'saveAt' => new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        ];
    }

    public function submitForReview(): void
    {
        if ($this->status !== ComplianceFolderStatus::DRAFT && $this->status !== ComplianceFolderStatus::PENDING_DOCS) {
            // ✅ On jette une nouvelle exception logique ici (à créer dans FolderStateException)
            throw new \DomainException("Le dossier ne peut pas être soumis dans son état actuel.");
        }

        if (!$this->hasAllMandatoryDocuments()) {
            throw FolderStateException::missingMandatoryDocuments($this->reference); // ✅ Corrigé
        }

        $this->status = ComplianceFolderStatus::IN_REVIEW;
        $this->submittedAt = new \DateTimeImmutable();
        $this->saveHistory('Dossier soumis pour analyse de conformité');
    }

    public function assignTo(User $reviewer): void
    {
        if ($this->status === ComplianceFolderStatus::APPROVED) { // ✅ Corrigé (APPROVED n'existe pas, c'est VALIDATED)
            throw new \DomainException("Un dossier validé ne peut pas être réassigné.");
        }

        $this->assignedReviewer = $reviewer;
        $this->saveHistory("Dossier assigné", "Analyste: {$reviewer->getFullName()}"); // ✅ Corrigé
    }

    public function approve(RiskLevel $riskLevel, User $approvedBy, string $comments = ''): void
    {
        if ($this->status !== ComplianceFolderStatus::IN_REVIEW) {
            throw FolderStateException::cannotApproveIfNotInReview($this->reference, $this->status->value); // ✅ Corrigé
        }

        $this->status = ComplianceFolderStatus::APPROVED;
        $this->riskLevel = $riskLevel;
        $this->isCertified = true;

        $this->nextReviewDate = match ($riskLevel) {
            RiskLevel::LOW => clone $this->createdAt->modify('+5 years'),
            RiskLevel::MEDIUM => clone $this->createdAt->modify('+3 years'),
            RiskLevel::HIGH => clone $this->createdAt->modify('+1 year'),
            RiskLevel::PROHIBITED => null,
        };

        if ($riskLevel === RiskLevel::HIGH) {
            $this->diligenceLevel = DiligenceLevel::ENHANCED;
        }

        $this->addMetadata('approval_comments', $comments);
        $this->saveHistory("Dossier validé", "Risque {$riskLevel->value} - Par {$approvedBy->getFullName()}"); // ✅ Corrigé
    }

    public function reject(string $reason, User $rejectedBy): void
    {
        $this->status = ComplianceFolderStatus::REJECTED;
        $this->addMetadata('rejection_reason', $reason);
        $this->saveHistory("Dossier rejeté", "Motif: {$reason} - Par {$rejectedBy->getFullName()}"); // ✅ Corrigé
    }

    private function hasAllMandatoryDocuments(): bool
    {
        foreach ($this->documents as $doc) {
            if ($doc->isMandatory && !$doc->isComplete()) {
                return false;
            }
        }
        return true;
    }

    /**
     * ✅ Règle métier : Verrouiller le dossier
     * @param User[] $allowedUsers
     */
    public function makeConfidential(array $allowedUsers): void
    {
        $this->isConfidential = true;
        $this->restrictedUsers->clear();

        foreach ($allowedUsers as $user) {
            $this->restrictedUsers->add($user);
        }

        $this->saveHistory('Dossier sécurisé', 'Accès restreint à une liste d\'utilisateurs spécifique.');
    }

    /**
     * ✅ Règle métier : Vérification d'accès
     */
    public function canBeViewedBy(User $user): bool
    {
        // Si pas confidentiel, tout le monde (dans le workspace) peut voir
        if (!$this->isConfidential) {
            return true;
        }

        // Si confidentiel, on vérifie la liste blanche
        return $this->restrictedUsers->contains($user);
    }

    public function unlock(): void
    {
        $this->isConfidential = false;
        $this->restrictedUsers->clear();
        $this->saveHistory('Confidentialité levée', 'Le dossier est de nouveau accessible à tous.');
    }

    public function requireDocument(DocumentType $type, bool $isMandatory = true): void
    {
        // On évite les doublons
        foreach ($this->documents as $doc) {
            if ($doc->type === $type) {
                return;
            }
        }

        // On crée l'attente de document (statut MISSING par défaut)
        $expectedDocument = ComplianceDocument::createExpected($this, $type, $isMandatory);

        $this->documents->add($expectedDocument);
    }

    public function isDraft(): bool
    {
        return $this->status === ComplianceFolderStatus::DRAFT;
    }
}
