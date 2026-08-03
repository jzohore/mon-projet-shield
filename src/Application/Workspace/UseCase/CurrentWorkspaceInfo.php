<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Response\WorkspaceInfoResponse;
use App\Domain\Firm\Exception\ProfileNotFoundException;
use App\Domain\Firm\Repository\RegulatoryProfileRepositoryInterface;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;

readonly class CurrentWorkspaceInfo
{
    public function __construct(
        private CurrentWorkspaceProvider $currentWorkspaceProvider,
        private RegulatoryProfileRepositoryInterface $regulatoryProfileRepository,
    ) {
    }

    public function __invoke(): WorkspaceInfoResponse
    {
        $workspace = $this->currentWorkspaceProvider->getWorkspace();

        $profile = $this->regulatoryProfileRepository->findOneByWorkspace(workspace: $workspace);

        if (!$profile instanceof \App\Domain\Firm\Entity\RegulatoryProfile) {
            throw ProfileNotFoundException::withWorkspaceName($workspace->name);
        }

        return WorkspaceInfoResponse::fromEntity(
            $workspace,
            $profile->filename,
            $profile->logoStoragePath,
            $profile->id?->toString(),
            $profile->oriasNumber,
            $profile->rcProInsurer,
        );
    }
}
