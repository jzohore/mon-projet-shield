<?php

namespace App\Application\Workspace\DTO\Response;

final readonly class WorkspaceInfoResponse
{
    public function __construct(
        public string $slugId,
        public string $name,
    ) {}
}
