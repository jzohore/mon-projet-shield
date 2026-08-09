<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Entity;

use App\Domain\Compliance\Enum\DocumentType;
use App\Domain\Kyc\Entity\Stakeholder;
use App\Domain\Kyc\Enum\DocumentStatus;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;

use function Symfony\Component\Clock\now;

use Symfony\Component\Uid\Uuid;

#[ORM\Entity()]
#[ORM\Table(name: 'compliance_documents')]
#[ORM\Index(name: 'idx_doc_client_status', columns: ['status'])]
class ComplianceDocument
{
    use GenerateSlugPrefixedTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public private(set) ?Uuid $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public private(set) string $slugId;

    #[ORM\ManyToOne(targetEntity: Stakeholder::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    public private(set) ?Stakeholder $stakeholder = null;

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
     * ex: "Attestation de provenance des fonds".
     */
    #[ORM\Column(length: 255, nullable: true)]
    public ?string $customLabel = null;

    #[ORM\Column(options: ['default' => false])]
    public bool $isAskToClient = false;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $filename = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $mimeType = null;

    #[ORM\Column(nullable: true)]
    public ?int $size = null;

    #[ORM\Column(length: 255, nullable: true)]
    public private(set) ?int $docuSealSubmissionId = null;

    #[ORM\Column(length: 255, nullable: true)]
    public private(set) ?string $docuSealDocumentUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    public private(set) ?\DateTimeImmutable $docuSealSignedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    public private(set) ?string $docuSealAuditLogUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    public private(set) ?string $docuSealSignatureUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    public private(set) ?string $docuSealRejectedReason = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $uploadedAt = null;

    private function __construct(#[ORM\ManyToOne(targetEntity: ComplianceFolder::class, inversedBy: 'documents')]
        #[ORM\JoinColumn(nullable: false)]
        public private(set) ComplianceFolder $folder, #[ORM\Column(type: 'string', enumType: DocumentType::class)]
        public private(set) DocumentType $type, #[ORM\Column]
        public bool $isMandatory)
    {
        $this->slugId = $this->generate_ulid_prefixed('comp_doc_');
    }

    public static function createExpected(ComplianceFolder $folder, DocumentType $type, bool $isMandatory = true): self
    {
        return new self($folder, $type, $isMandatory);
    }

    /**
     * Action métier : Le client a uploadé le fichier.
     */
    public function markAsUploaded(string $storagePath, string $filename, string $mimeType, int $size): void
    {
        $this->storagePath = $storagePath;
        $this->status = DocumentStatus::UPLOADED;
        $this->rejectionReason = null;
        $this->uploadedAt = now();
        $this->filename = $filename;
        $this->mimeType = $mimeType;
        $this->size = $size;

        $subject = 'la société';
        if ($this->stakeholder instanceof Stakeholder) {
            $subject = sprintf('%s %s', $this->stakeholder->firstName, $this->stakeholder->lastName);
        }

        $this->folder->saveHistory(
            'Document téléversé',
            sprintf(
                'Le document "%s" concernant %s a été ajouté au dossier.',
                $this->type->getLabel(),
                $subject
            )
        );
    }

    public function markAsPending(string $email): void
    {
        $this->status = DocumentStatus::PENDING;
        $this->folder->saveHistory(
            'DER Généré',
            sprintf(
                'Le document d\'entrée en Relation a été généré automatiquement par %s.',
                $email
            )
        );
    }

    public function markAsProcessed(): void
    {
        $this->status = DocumentStatus::PROCESSING;
        $this->rejectionReason = null;
    }

    public function markAsGenerated(string $pdfPath): void
    {
        $this->status = DocumentStatus::GENERATED;
        $this->storagePath = $pdfPath;
    }

    public function markAsFailed(): void
    {
        $this->status = DocumentStatus::FAILED;
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
     */
    public function setExtractedData(array $data): void
    {
        $this->ocrData = $data;
    }

    public function isComplete(): bool
    {
        // Si tu stockes le nom du fichier ou son chemin,
        // le document est complet si cette propriété n'est pas nulle.
        return $this->isAskToClient || null !== $this->storagePath;
    }

    public function setAskToClient(bool $ask): void
    {
        $this->isAskToClient = $ask;
    }

    public function setDocuSealSubmissionId(?int $id): void
    {
        $this->docuSealSubmissionId = $id;
    }

    public function setDocuSealDocumentUrl(?string $url): void
    {
        $this->docuSealDocumentUrl = $url;
    }

    public function setDocuSealSignedAt(?\DateTimeImmutable $signedAt): void
    {
        $this->docuSealSignedAt = $signedAt;
    }

    public function setDocuSealAuditLogUrl(?string $auditLogUrl): void
    {
        $this->docuSealAuditLogUrl = $auditLogUrl;
    }

    public function setDocuSealRejectedReason(?string $reason): void
    {
        $this->docuSealRejectedReason = $reason;
    }

    public function setDocuSealSignatureUrl(?string $signatureUrl): void
    {
        $this->docuSealSignatureUrl = $signatureUrl;
    }
}
