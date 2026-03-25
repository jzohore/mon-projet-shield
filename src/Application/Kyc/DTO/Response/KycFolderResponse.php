<?php

namespace App\Application\Kyc\DTO\Response;

use App\Domain\Kyc\Entity\KycDocument;
use App\Domain\Kyc\Entity\KycFolder;
use App\Domain\Kyc\Entity\Stakeholder;
use App\Domain\Kyc\Enum\KycFolderStatus;
use Doctrine\Common\Collections\Collection;

final readonly class KycFolderResponse
{
    /**
     * @param Collection<int, KycDocument> $documents
     * @param Collection<int, Stakeholder> $stakeholders
     * @param array<int, array{title: string, description: string, saveAt: \DateTimeImmutable}> $history
     */
    public function __construct(
        public string $reference,
        public string $contactEmail,
        public string $contactFirstName,
        public string $contactLastName,
        public string $slugId,
        public KycFolderStatus $status,
        public Collection $stakeholders,
        public Collection $documents,
        public ?string $companyName = null,
        public ?string $siret = null,
        public ?string $siren = null,
        public ?\DateTimeImmutable $createdAt = null,
        public array $history = [],
        public ?string $shareToken = null,
        public bool $isShareTokenValid = false,
        public ?string $workspaceName = null,
        public ?string $legalCategory = null,
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
            stakeholders: $kycFolder->stakeholders,
            documents: $kycFolder->documents,
            companyName: $kycFolder->companyName,
            siret: $kycFolder->siret,
            siren: $kycFolder->siren,
            createdAt: $kycFolder->createdAt,
            history: $kycFolder->history ?? [],
            shareToken: $kycFolder->shareToken,
            isShareTokenValid: $kycFolder->isShareTokenValid(),
            workspaceName: $workspaceName,
            legalCategory: $kycFolder->legalCategory,
        );
    }
}
