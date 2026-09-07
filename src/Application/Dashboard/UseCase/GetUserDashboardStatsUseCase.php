<?php

declare(strict_types=1);

namespace App\Application\Dashboard\UseCase;

use App\Application\Dashboard\DTO\UserDashboardStats;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Firm\Entity\RegulatoryProfile;
use App\Domain\Firm\Repository\RegulatoryProfileRepositoryInterface;
use App\Domain\User\Repository\ClientRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;

readonly class GetUserDashboardStatsUseCase
{
    public function __construct(
        private ComplianceFolderRepositoryInterface $complianceFolderRepository,
        private ClientRepositoryInterface $clientRepository,
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private RegulatoryProfileRepositoryInterface $regulatoryProfileRepository,
        private CurrentWorkspaceProvider $workspaceProvider,
        private CurrentUserProvider $userProvider,
    ) {
    }

    public function __invoke(): UserDashboardStats
    {
        $workspace = $this->workspaceProvider->getWorkspace();
        $user = $this->userProvider->getUser();

        $profile = $this->regulatoryProfileRepository->findOneByWorkspace($workspace);
        $isRegProfileValid = $profile instanceof RegulatoryProfile && $profile->isProfileValid();

        $workspaceId = $workspace->id?->toString() ?? '';

        return new UserDashboardStats(
            workspaceName: $workspace->name,
            isFirm: $workspace->isFirm(),
            hasActiveSubscription: $workspace->hasActiveSubscription(),
            canOpenFolder: $workspace->canOpenComplianceFolder(),
            trialDossiersRemaining: $workspace->trialDossiersRemaining,
            remainingMeetingMinutes: $workspace->remainingMeetingMinutes(),
            meetingMinutesAllocated: $workspace->meetingMinutesAllocated,
            activeFoldersCount: $this->complianceFolderRepository->countActiveForWorkspace($workspace),
            draftFoldersCount: $this->complianceFolderRepository->countDraftsForWorkspace($workspace),
            totalFoldersCount: $this->complianceFolderRepository->countForWorkspace($workspace),
            clientsCount: $this->clientRepository->findAllByWorkspace($workspace, null, 'recent', true)->getNbResults(),
            teamMembersCount: \count($this->workspaceMemberRepository->findByWorkspace($workspaceId)),
            isOrgCompleted: $workspace->isOrgCompleted(),
            isRegProfileValid: $isRegProfileValid,
            is2faEnabled: $user->isGoogleAuthenticatorEnabled(),
        );
    }
}
