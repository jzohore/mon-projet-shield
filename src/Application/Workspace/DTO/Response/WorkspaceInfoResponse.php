<?php

namespace App\Application\Workspace\DTO\Response;

use App\Domain\Workspace\Entity\Workspace;

final readonly class WorkspaceInfoResponse
{
    public function __construct(
        public ?string $slugId = null,
        public ?string $name = null,
    ) {}

    public static function fromEntity(Workspace $workspace): WorkspaceInfoResponse
    {
        return new self(
            $workspace->slugId,
            $workspace->name,
        );
    }
}
