<?php

namespace App\Domain\Workspace\Repository;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceMember;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Uid\Uuid;

interface WorkspaceMemberRepositoryInterface
{
    public function save(WorkspaceMember $workspaceMember): void;

    public function findByWorkspaceAndUser(Workspace $workspace, User $user): ?WorkspaceMember;

    /**
     * @param string $workspaceId
     * @return array<int, WorkspaceMember>
     */
    public function findByWorkspace(string $workspaceId): array;

    public function delete(WorkspaceMember $workspaceMember): void;

    /**
     * @param User $user
     * @return array<int, WorkspaceMember>
     */
    public function findByUser(User $user): array;

    public function findOneByUser(Uuid $userId): ?WorkspaceMember;

    public function isUserAdminOfWorkspace(User $user, Workspace $workspace): bool;

    /**
     * @param string $workspaceSlugId
     * @param string|null $search
     * @return Pagerfanta<WorkspaceMember>
     */
    public function getMembersList(string $workspaceSlugId, ?string $search = null): Pagerfanta;

    /**
     * @param string $workspaceSlugId
     * @return array<int, WorkspaceMember>
     */
    public function getMembersActive(string $workspaceSlugId): array;
}
