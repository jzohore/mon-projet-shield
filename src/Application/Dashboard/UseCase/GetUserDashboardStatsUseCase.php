<?php

namespace App\Application\Dashboard\UseCase;

use App\Application\Dashboard\DTO\UserDashboardStats;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;

readonly class GetUserDashboardStatsUseCase
{
    public function __construct(
        private ComplianceFolderRepositoryInterface $complianceFolderRepository,
        private CurrentWorkspaceProvider $workspaceProvider,
    ) {}

    public function __invoke(): UserDashboardStats
    {
        $workspace = $this->workspaceProvider->getWorkspace();

        $folderDrafts = $this->complianceFolderRepository->countDraftsForWorkspace($workspace);

        return new UserDashboardStats(
            folderDraftWorkspaces: $folderDrafts,
        );
    }
}
