<?php

declare(strict_types=1);

namespace App\Application\Workspace\DTO\Response;

use App\Domain\Workspace\Entity\Workspace;

final readonly class WorkspaceInfoResponse
{
    public function __construct(
        // 1. Les paramètres obligatoires TOUJOURS en premier
        public string $type,
        public bool $isFirm,
        // 2. Les paramètres avec valeur par défaut ensuite
        public int $balance = 0,
        public bool $isActivated = true,
        public bool $isSiretValid = true,
        public ?string $slugId = null,
        public ?string $name = null,
        public ?string $legalName = null,
        public ?string $siret = null,
        public ?string $industry = null,
        public ?string $address = null,
        public ?string $suspensionReason = null,
        public ?string $regulatoryProfilId = null,
        public ?string $logoFilename = null,
        public ?string $logoStoragePath = null,
        public ?string $oriasNumber = null,
        public ?string $rcProInsurer = null,
        public ?\DateTimeImmutable $suspendedAt = null,
    ) {
    }

    public static function fromEntity(
        Workspace $workspace,
        ?string $filename = null,
        ?string $logoStoragePath = null,
        ?string $profileId = null,
        ?string $oriasNumber = null,
        ?string $rcProInsurer = null,
    ): self {
        return new self(
            type: $workspace->type->value,
            isFirm: $workspace->isFirm(),
            balance: $workspace->balance,
            isActivated: $workspace->isActive,
            isSiretValid: $workspace->isSiretValid,
            slugId: $workspace->slugId,
            name: $workspace->name,
            legalName: $workspace->legalName,
            siret: $workspace->siret, // Cast en string si nécessaire pour PHPStan
            industry: $workspace->industry?->getLabel(),
            address: (string) $workspace->address,
            suspensionReason: $workspace->suspensionReason,
            regulatoryProfilId: $profileId,
            logoFilename: $filename,
            logoStoragePath: $logoStoragePath,
            oriasNumber: $oriasNumber,
            rcProInsurer: $rcProInsurer,
            suspendedAt: $workspace->suspendedAt,
        );
    }
}
