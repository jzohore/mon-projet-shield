<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Repository;

use App\Domain\Workspace\Entity\Workspace;

interface WorkspaceRepositoryInterface
{
    public function save(Workspace $workspace): void;
    public function findOneBySlug(string $slug): ?Workspace;

    public function findMembersByWorkspaceId(string $workspaceId): array;

}
