<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Repository;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceMember;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Uid\Uuid;

interface WorkspaceMemberRepositoryInterface
{
    public function save(WorkspaceMember $workspaceMember, bool $flush = true): void;

    public function findByWorkspaceAndUser(Workspace $workspace, User $user): ?WorkspaceMember;

    /**
     * @return array<int, WorkspaceMember>
     */
    public function findByWorkspace(string $workspaceId): array;

    public function delete(WorkspaceMember $workspaceMember): void;

    /**
     * @return array<int, WorkspaceMember>
     */
    public function findByUser(User $user): array;

    public function findOneByUser(Uuid $userId): ?WorkspaceMember;

    public function findOneByWorkspace(Uuid $workspaceId): ?WorkspaceMember;

    public function findOwnerByWorkspace(Uuid $workspaceId): ?WorkspaceMember;

    public function isUserAdminOfWorkspace(User $user, Workspace $workspace): bool;

    /**
     * @return Pagerfanta<WorkspaceMember>
     */
    public function getMembersList(Workspace $workspace, ?string $search = null): Pagerfanta;

    /**
     * @return array<int, WorkspaceMember>
     */
    public function getMembersActive(string $workspaceSlugId): array;

    public function isAlreadyMember(Workspace $workspace, string $email): bool;

    public function findOneByUserSlugAndWorkspace(string $userSlugId, string $workspaceId): ?WorkspaceMember;

    /**
     * @return list<User>
     */
    public function findMembersAdmin(Workspace $workspace): array;
}
