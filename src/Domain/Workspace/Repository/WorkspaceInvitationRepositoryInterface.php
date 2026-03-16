<?php

namespace App\Domain\Workspace\Repository;

use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;

interface WorkspaceInvitationRepositoryInterface
{
    public function save(WorkspaceInvitation $workspaceInvitation): void;

    /**
     * @return WorkspaceInvitation[]
     */
    public function findByWorkspace(Workspace $workspace): array;

    public function findByEmail(string $email): ?WorkspaceInvitation;
}
