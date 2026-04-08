<?php

namespace App\Application\Workspace\DTO\Response;

use App\Domain\Workspace\Entity\Workspace;

final readonly class WorkspaceInfoResponse
{
    public function __construct(
        public ?string $slugId = null,
        public ?string $name = null,
        public ?string $legalName = null,
        public ?string $siret = null,
        public ?string $industry = null,
        public ?string $address = null,
        public int $balance = 0,
    ) {}

    public static function fromEntity(Workspace $workspace): WorkspaceInfoResponse
    {
        return new self(
            $workspace->slugId,
            $workspace->name,
            $workspace->legalName,
            $workspace->siret,
            $workspace->industry?->getLabel(),
            $workspace->address,
            $workspace->balance,
        );
    }
}
