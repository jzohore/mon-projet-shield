<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Entity;

use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Enum\DiligenceLevel;
use App\Domain\Compliance\Enum\DocumentType;
use App\Domain\Compliance\Enum\MeetingProcessingStatus;
use App\Domain\Compliance\Enum\RiskLevel;
use App\Domain\Compliance\Exception\FolderStateException;
use App\Domain\User\Entity\Client;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;

use function Symfony\Component\Clock\now;

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

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public private(set) string $slugId;

    #[ORM\Column(type: 'string', enumType: ComplianceFolderStatus::class)]
    public private(set) ComplianceFolderStatus $status = ComplianceFolderStatus::DRAFT;

    /** @var Collection<int, ComplianceDocument> */
    #[ORM\OneToMany(targetEntity: ComplianceDocument::class, mappedBy: 'folder', cascade: ['persist', 'remove'])]
    public private(set) Collection $documents;

    /** @var Collection<int, MeetingRecording> */
    #[ORM\OneToMany(targetEntity: MeetingRecording::class, mappedBy: 'complianceFolder', cascade: ['persist', 'remove'])]
    public private(set) Collection $meetingRecordings;

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

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'folders')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    public private(set) ?User $assignedReviewer = null;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    public private(set) ?array $metadata = [];

    #[ORM\Column]
    public private(set) bool $isConfidential = false;

    /** @var Collection<int, User> */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'folders')]
    #[ORM\JoinTable(name: 'compliance_folder_restricted_users')]
    public private(set) Collection $restrictedUsers;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'complianceFolders')]
    #[ORM\JoinColumn(nullable: true)] // Un dossier appartient obligatoirement à un client
    public private(set) ?Client $client = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    public private(set) ?array $postMeetingReport = null;

    // Chronomètre anti-fraude côté serveur
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public private(set) ?\DateTimeImmutable $currentRecordingStartedAt = null;

    // Facturation et analytique
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    public private(set) int $totalAudioDurationSeconds = 0;

    #[ORM\Column(type: 'string', nullable: true, enumType: MeetingProcessingStatus::class)]
    public private(set) ?MeetingProcessingStatus $meetingProcessingStatus = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    public private(set) ?string $audioMimeType = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['default' => false])]
    public private(set) bool $isAcceptRecording = false;

    /**
     * ✅ Constructeur protégé (DDD).
     */
    protected function __construct(#[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'folders')]
        #[ORM\JoinColumn(nullable: false)]
        public private(set) Workspace $workspace, #[ORM\Column(length: 20, unique: true)]
        public private(set) string $reference, string $email, #[ORM\Column]
        public private(set) string $creationMethod)
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->documents = new ArrayCollection();
        $this->saveHistory(
            'Dossier initié',
            sprintf('Dossier créé par : %s', $email)
        );
        $this->slugId = $this->generate_ulid_prefixed('comp_fol_');
        $this->restrictedUsers = new ArrayCollection();
        $this->meetingRecordings = new ArrayCollection();
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
     * ✅ Rendu du second argument optionnel pour plus de flexibilité.
     */
    public function saveHistory(string $event, string $data = ''): void
    {
        $this->history[] = [
            'title' => $event,
            'description' => $data,
            'saveAt' => now(),
        ];
    }

    public function submitForReview(): void
    {
        if (!in_array($this->status, [ComplianceFolderStatus::DRAFT, ComplianceFolderStatus::PENDING_DOCS, ComplianceFolderStatus::AWAITING_CLIENT], true)) {
            // ✅ On jette une nouvelle exception logique ici (à créer dans FolderStateException)
            throw new \DomainException('Le dossier ne peut pas être soumis dans son état actuel.');
        }

        if (!$this->hasAllMandatoryDocuments()) {
            throw FolderStateException::missingMandatoryDocuments($this->reference); // ✅ Corrigé
        }

        $this->status = ComplianceFolderStatus::IN_REVIEW;
        $this->submittedAt = now();
        $this->saveHistory('Dossier soumis pour analyse de conformité');
    }

    public function assignTo(User $reviewer): void
    {
        if (ComplianceFolderStatus::APPROVED === $this->status) { // ✅ Corrigé (APPROVED n'existe pas, c'est VALIDATED)
            throw new \DomainException('Un dossier validé ne peut pas être réassigné.');
        }

        $this->assignedReviewer = $reviewer;
        $this->saveHistory('Dossier assigné', "Analyste: {$reviewer->getFullName()}"); // ✅ Corrigé
    }

    public function approve(RiskLevel $riskLevel, User $approvedBy, string $comments = ''): void
    {
        if (ComplianceFolderStatus::IN_REVIEW !== $this->status) {
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

        if (RiskLevel::HIGH === $riskLevel) {
            $this->diligenceLevel = DiligenceLevel::ENHANCED;
        }

        $this->addMetadata('approval_comments', $comments);
        $this->saveHistory('Dossier validé', "Risque {$riskLevel->value} - Par {$approvedBy->getFullName()}"); // ✅ Corrigé
    }

    public function reject(string $reason, User $rejectedBy): void
    {
        $this->status = ComplianceFolderStatus::REJECTED;
        $this->addMetadata('rejection_reason', $reason);
        $this->saveHistory('Dossier rejeté', "Motif: {$reason} - Par {$rejectedBy->getFullName()}"); // ✅ Corrigé
    }

    public function hasAllMandatoryDocuments(): bool
    {
        foreach ($this->documents as $doc) {
            if ($doc->isMandatory && !$doc->isComplete()) {
                return false;
            }
        }

        return true;
    }

    public function canBeSubmitted(): bool
    {
        // Règle 1 : Il doit être dans un état où la collecte est encore active.
        $isCollectionActive = in_array($this->status, [
            ComplianceFolderStatus::AWAITING_CLIENT,
            ComplianceFolderStatus::PENDING_DOCS,
        ], true);

        // Règle 2 : Toutes les pièces obligatoires sont présentes.
        return $isCollectionActive && $this->hasAllMandatoryDocuments();
    }

    /**
     * ✅ Règle métier : Verrouiller le dossier.
     *
     * @param User[] $allowedUsers
     */
    public function makeConfidential(array $allowedUsers): void
    {
        $this->isConfidential = true;
        // $this->restrictedUsers->clear();

        foreach ($allowedUsers as $user) {
            $this->restrictedUsers->add($user);
        }

        $this->saveHistory('Dossier sécurisé', 'Accès restreint à une liste d\'utilisateurs spécifique.');
    }

    public function markAsArchive(string $email): void
    {
        $this->status = ComplianceFolderStatus::ARCHIVED;
        $this->saveHistory('Dossier archivé', 'Le dossier à été mis en archive par : ' . $email);
    }

    public function markAsDeleted(): void
    {
        $this->status = ComplianceFolderStatus::DELETED;
    }

    /**
     * ✅ Règle métier : Vérification d'accès.
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
        return ComplianceFolderStatus::DRAFT === $this->status;
    }

    public function isArchived(): bool
    {
        return ComplianceFolderStatus::ARCHIVED === $this->status;
    }

    public function markAsDerGenerated(): void
    {
        $this->status = ComplianceFolderStatus::DER_GENERATED;
    }

    public function markAsDerSent(): void
    {
        $this->status = ComplianceFolderStatus::DER_SENT;

        $this->saveHistory(
            $this->status->getLabel(),
            'Le Document d\'Entrée en Relation (DER) a été notifié au client avec succès pour signature électronique.'
        );
    }

    public function markAsDerOpened(string $date): void
    {
        $this->status = ComplianceFolderStatus::DER_OPENED;

        $this->saveHistory(
            $this->status->getLabel(),
            sprintf('Le document a été consulté par le client depuis son espace sécurisé le %s.', $date)
        );
    }

    public function markAsDerApproved(string $date): void
    {
        $this->status = ComplianceFolderStatus::DER_SIGNED;

        // On renforce le côté légal de l'action
        $this->saveHistory(
            $this->status->getLabel(),
            sprintf('Le DER a été signé électroniquement. Certificat d\'audit scellé le %s.', $date)
        );
    }

    public function markAsDerRejected(string $date, ?string $declineReason): void
    {
        $this->status = ComplianceFolderStatus::DER_REJECTED;

        // Un refus n'est pas juste un "rejet", c'est une suspension de la procédure KYC
        $this->saveHistory(
            $this->status->getLabel(),
            sprintf('La signature a été refusée par le client le %s pour la raison suivant %s. Le processus d\'entrée en relation est suspendu.',
                $date,
                $declineReason,
            )
        );
    }

    public function attachClient(Client $client): void
    {
        $this->client = $client;
    }

    /**
     * Transitionne le dossier en attente de la première action du client.
     * Généralement appelé immédiatement après la signature du DER.
     */
    public function markAsAwaitingClient(string $date): void
    {
        $this->status = ComplianceFolderStatus::AWAITING_CLIENT;

        // Trace d'audit pour l'AMF
        $this->saveHistory(
            $this->status->getLabel(),
            sprintf('Espace sécurisé activé. En attente de la connexion et de la soumission du dossier par le client depuis le %s.', $date)
        );
    }

    /**
     * Transitionne le dossier en mode "Collecte active".
     * Utilisé lorsqu'il manque des pièces (ex: le client a fourni la CNI mais pas le justificatif de domicile).
     */
    public function markAsPendingDocs(string $date, ?int $missingCount = null): void
    {
        $this->status = ComplianceFolderStatus::PENDING_DOCS;

        // Message dynamique pour la traçabilité du CGP
        $message = null !== $missingCount && $missingCount > 0
            ? sprintf('Collecte KYC en cours. %d pièce(s) réglementaire(s) requise(s) en date du %s.', $missingCount, $date)
            : sprintf('Reprise de la collecte KYC. En attente de transmission de nouvelles pièces par le client le %s.', $date);

        // Trace d'audit pour l'AMF
        $this->saveHistory(
            $this->status->getLabel(),
            $message
        );
    }

    public function startRecordingSession(): void
    {
        if (!$this->currentRecordingStartedAt instanceof \DateTimeImmutable) {
            $this->currentRecordingStartedAt = new \DateTimeImmutable();
        }
    }

    public function stopRecordingSession(): void
    {
        if ($this->currentRecordingStartedAt instanceof \DateTimeImmutable) {
            $duration = time() - $this->currentRecordingStartedAt->getTimestamp();
            $this->totalAudioDurationSeconds += max(0, $duration);
            $this->currentRecordingStartedAt = null;
        }
    }

    /**
     * @param array<string, mixed> $report
     */
    public function setPostMeetingReport(array $report): void
    {
        $this->postMeetingReport = $report;
    }

    public function setMeetingProcessingStatus(?MeetingProcessingStatus $status): void
    {
        $this->meetingProcessingStatus = $status;
    }

    public function getMeetingProcessingStatus(): ?MeetingProcessingStatus
    {
        return $this->meetingProcessingStatus;
    }

    public function setAudioMimeType(?string $mimeType): void
    {
        $this->audioMimeType = $mimeType;
    }

    public function markAsRecording(): void
    {
        $this->isAcceptRecording = true;
    }

    public function addMeetingRecording(MeetingRecording $recording): void
    {
        if (!$this->meetingRecordings->contains($recording)) {
            $this->meetingRecordings->add($recording);
        }
    }

    abstract public function isDraftEmpty(): bool;
}
