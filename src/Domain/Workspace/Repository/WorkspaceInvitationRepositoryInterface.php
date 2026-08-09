<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Repository;

use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use Symfony\Component\Uid\Uuid;

interface WorkspaceInvitationRepositoryInterface
{
    public function save(WorkspaceInvitation $workspaceInvitation, bool $flush = true): void;

    /**
     * @return WorkspaceInvitation[]
     */
    public function findByWorkspace(Workspace $workspace): array;

    public function findByEmail(string $email): ?WorkspaceInvitation;

    public function findBySlugId(string $slugId): ?WorkspaceInvitation;

    public function findByToken(string $token): ?WorkspaceInvitation;

    public function delete(WorkspaceInvitation $workspaceInvitation): void;

    public function countMemberInvitation(?string $workspaceId = null): bool|float|int|string|null;

    public function getById(Uuid $id): ?WorkspaceInvitation;

    public function hasPendingInvitation(Workspace $workspace, string $email): bool;
}
