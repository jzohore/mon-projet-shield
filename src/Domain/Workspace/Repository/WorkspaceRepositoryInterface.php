<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Repository;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;

interface WorkspaceRepositoryInterface
{
    public function save(Workspace $workspace): void;
    public function findOneBySlug(string $slug): ?Workspace;

    /**
     * @return Workspace[]|User[]
     */
    public function findMembersByWorkspaceId(string $workspaceId): array;

    public function findOneByName(?string $name): ?Workspace;

}
