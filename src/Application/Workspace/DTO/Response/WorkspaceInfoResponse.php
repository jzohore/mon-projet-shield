<?php

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
        public ?string $slugId = null,
        public ?string $name = null,
        public ?string $legalName = null,
        public ?string $siret = null,
        public ?string $industry = null,
        public ?string $address = null,
    ) {}

    public static function fromEntity(Workspace $workspace): self
    {
        // Utiliser les arguments nommés règle TOUS tes problèmes d'ordre
        return new self(
            type: $workspace->type->value,
            isFirm: $workspace->isFirm(),
            balance: $workspace->balance,
            slugId: $workspace->slugId,
            name: $workspace->name,
            legalName: $workspace->legalName,
            siret: (string) $workspace->siret, // Cast en string si nécessaire pour PHPStan
            industry: $workspace->industry?->getLabel(),
            address: (string) $workspace->address,
        );
    }
}
