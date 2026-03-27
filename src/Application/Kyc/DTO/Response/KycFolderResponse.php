<?php

namespace App\Application\Kyc\DTO\Response;

use App\Domain\Kyc\Entity\KycDocument;
use App\Domain\Kyc\Entity\KycFolder;
use App\Domain\Kyc\Enum\KycFolderStatus;

final readonly class KycFolderResponse
{
    /**
     * @param string $reference
     * @param string $contactEmail
     * @param string $contactFirstName
     * @param string $contactLastName
     * @param string $slugId
     * @param KycFolderStatus $status
     * @param array<int, array<string, mixed>> $stakeholders
     * @param array<int, array<string, mixed>> $documents
     * @param string|null $companyName
     * @param string|null $siret
     * @param string|null $siren
     * @param \DateTimeImmutable|null $createdAt
     * @param array<int, array{title: string, description: string, saveAt: \DateTimeImmutable}> $history
     * @param string|null $shareToken
     * @param bool $isShareTokenValid
     * @param string|null $workspaceName
     * @param string|null $legalCategory
     */
    public function __construct(
        public string $reference,
        public string $contactEmail,
        public string $contactFirstName,
        public string $contactLastName,
        public string $slugId,
        public KycFolderStatus $status,
        public array $stakeholders,
        public array $documents,
        public ?string $companyName = null,
        public ?string $siret = null,
        public ?string $siren = null,
        public ?\DateTimeImmutable $createdAt = null,
        public array $history = [],
        public ?string $shareToken = null,
        public bool $isShareTokenValid = false,
        public ?string $workspaceName = null,
        public ?string $legalCategory = null,
        public ?bool $isCertified = false,
    ) {}

    public static function fromEntity(KycFolder $kycFolder, ?string $workspaceName = null): self
    {
        return new self(
            reference: $kycFolder->reference,
            contactEmail: $kycFolder->contactEmail,
            contactFirstName: $kycFolder->contactFirstName,
            contactLastName: $kycFolder->contactLastName,
            slugId: $kycFolder->slugId,
            status: $kycFolder->status,
            stakeholders: $kycFolder->stakeholders->map(fn($s) => [
                'fullName' => $s->firstName . ' ' . $s->lastName,
                'roleLabel' => $s->role->getLabel(),
                'isUbo' => $s->isUbo,
                'percentage' => $s->ownershipPercentage,
                'slugId' => $s->slugId,
            ])->toArray(),
            documents: $kycFolder->documents->map(fn(KycDocument $doc) => [
                'slugId'          => $doc->slugId,
                'typeLabel'       => $doc->type->value, // ex: "KBIS"
                'status'          => $doc->status->value, // ex: "PENDING"
                'storagePath'     => $doc->storagePath,
                'rejectionReason' => $doc->rejectionReason,
                'expiresAt'       => $doc->expiresAt?->format('d/m/Y'),
                // 💡 On ne prend que l'ID du stakeholder pour éviter la boucle infinie
                'stakeholderSlug' => $doc->stakeholder?->slugId,
            ])->toArray(),
            companyName: $kycFolder->companyName,
            siret: $kycFolder->siret,
            siren: $kycFolder->siren,
            createdAt: $kycFolder->createdAt,
            history: $kycFolder->history ?? [],
            shareToken: $kycFolder->shareToken,
            isShareTokenValid: $kycFolder->isShareTokenValid(),
            workspaceName: $workspaceName,
            legalCategory: $kycFolder->legalCategory,
            isCertified: $kycFolder->isCertified,
        );
    }
}
