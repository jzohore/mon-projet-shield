<?php

declare(strict_types=1);

namespace App\Application\Firm\DTO\Response;

use App\Domain\Firm\Entity\RegulatoryProfile;

final readonly class RegulatoryProfilResponse
{
    /**
     * @param array<int, array{name: string|null, address: string|null, email: string|null, phone: string|null}> $partners
     */
    public function __construct(
        public string $workspaceName, // Supposé provenir de la relation Workspace
        public ?string $logoStoragePath = null,
        public ?string $filename = null,
        public ?string $mimeType = null,
        public ?int $size = null,
        public ?string $oriasNumber = null,
        public ?string $professionalAssociation = null,
        public ?string $rcProInsurer = null,
        public ?string $rcProPolicyNumber = null,
        public bool $isIndependent = true,
        public ?string $signatureBase64 = null,
        public array $partners = [],
    ) {
    }

    public static function fromEntity(RegulatoryProfile $profile): self
    {
        return new self(
            workspaceName: $profile->workspace->name, // Adapte selon la propriété exacte de ton entité Workspace
            logoStoragePath: $profile->logoStoragePath,
            filename: $profile->filename,
            mimeType: $profile->mimeType,
            size: $profile->size,
            oriasNumber: $profile->oriasNumber,
            professionalAssociation: $profile->professionalAssociation,
            rcProInsurer: $profile->rcProInsurer,
            rcProPolicyNumber: $profile->rcProPolicyNumber,
            isIndependent: $profile->isIndependent,
            signatureBase64: $profile->signatureBase64,
            partners: $profile->partners,
        );
    }
}
