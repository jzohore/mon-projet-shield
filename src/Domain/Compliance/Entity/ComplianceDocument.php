<?php

namespace App\Domain\Compliance\Entity;

use App\Domain\Kyc\Entity\Stakeholder;
use App\Domain\Kyc\Enum\DocumentStatus;
use App\Domain\Kyc\Enum\DocumentType;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity()]
#[ORM\Table(name: 'compliance_documents')]
class ComplianceDocument
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

    #[ORM\ManyToOne(targetEntity: ComplianceFolder::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    public private(set) ComplianceFolder $folder;

    #[ORM\ManyToOne(targetEntity: Stakeholder::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    public private(set) ?Stakeholder $stakeholder = null;

    #[ORM\Column(type: 'string', enumType: DocumentType::class)]
    public private(set) DocumentType $type;

    #[ORM\Column(type: 'string', enumType: DocumentStatus::class)]
    public private(set) DocumentStatus $status = DocumentStatus::PENDING;

    #[ORM\Column(length: 255, nullable: true)]
    public private(set) ?string $storagePath = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public private(set) ?string $rejectionReason = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    public private(set) ?\DateTimeImmutable $expiresAt = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    public private(set) ?array $ocrData = null;

    /**
     * Si le type est "OTHER", on utilise ce label pour afficher au client
     * ex: "Attestation de provenance des fonds"
     */
    #[ORM\Column(length: 255, nullable: true)]
    public ?string $customLabel = null;

    #[ORM\Column]
    public bool $isMandatory = true;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $uploadedAt = null;

    private function __construct(ComplianceFolder $folder, DocumentType $type, bool $isMandatory)
    {
        $this->folder = $folder;
        $this->type = $type;
        $this->isMandatory = $isMandatory;
        $this->slugId = $this->generate_ulid_prefixed('comp_doc_');
    }

    public static function createExpected(ComplianceFolder $folder, DocumentType $type, bool $isMandatory = true): self
    {
        return new self($folder, $type, $isMandatory);
    }

    /**
     * Action métier : Le client a uploadé le fichier.
     */
    public function markAsUploaded(string $storagePath): void
    {
        $this->storagePath = $storagePath;
        $this->status = DocumentStatus::UPLOADED;
        $this->rejectionReason = null; // On nettoie l'ancien refus éventuel
    }

    public function markAsProcessed(): void
    {
        $this->status = DocumentStatus::VALID;
        $this->rejectionReason = null;
    }

    /**
     * Action métier : L'avocat refuse le document.
     */
    public function reject(string $reason): void
    {
        $this->status = DocumentStatus::REJECTED;
        $this->rejectionReason = $reason;
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    public function setExtractedData(array $data): void
    {
        $this->ocrData = $data;
    }

    public function isComplete(): bool
    {
        // Si tu stockes le nom du fichier ou son chemin,
        // le document est complet si cette propriété n'est pas nulle.
        return $this->storagePath !== null;
    }
}
