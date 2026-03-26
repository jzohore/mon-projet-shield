<?php

namespace App\Application\User\UseCase\Dashboard;

use App\Application\Workspace\UseCase\GetCurrentWorkspaceInfo;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;

final readonly class GetCountMemberUseCase
{
    public function __construct(
        private WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
        private GetCurrentWorkspaceInfo $getCurrentWorkspaceInfo,
    ) {}

    public function __invoke(User $user): bool|float|int|string|null
    {
        $workspace = ($this->getCurrentWorkspaceInfo)($user);

        return $this->workspaceInvitationRepository->countMemberInvitation(workspaceId: $workspace->slugId);
    }
}
