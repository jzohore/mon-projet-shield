<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Dashboard;

use App\Application\Workspace\UseCase\GetCurrentWorkspaceInfo;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use Webmozart\Assert\Assert;

final readonly class GetCountMemberUseCase
{
    public function __construct(
        private WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
        private GetCurrentWorkspaceInfo $getCurrentWorkspaceInfo,
    ) {
    }

    public function __invoke(User $user): bool|float|int|string|null
    {
        $userId = $user->id;
        Assert::notNull($userId, "L'utilisateur doit avoir un ID pour récupérer le workspace.");
        $workspace = ($this->getCurrentWorkspaceInfo)($userId);

        return $this->workspaceInvitationRepository->countMemberInvitation(workspaceId: $workspace->slugId);
    }
}
