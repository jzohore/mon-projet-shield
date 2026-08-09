<?php

declare(strict_types=1);

namespace App\Application\Firm\UseCase;

use App\Application\Firm\DTO\Response\RegulatoryProfilResponse;
use App\Domain\Firm\Exception\ProfileNotFoundException;
use App\Domain\Firm\Repository\RegulatoryProfileRepositoryInterface;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;

readonly class CurrentRegulatoryProfileInfo
{
    public function __construct(
        private CurrentWorkspaceProvider $currentWorkspaceProvider,
        private RegulatoryProfileRepositoryInterface $regulatoryProfileRepository,
    ) {
    }

    public function __invoke(): RegulatoryProfilResponse
    {
        $workspace = $this->currentWorkspaceProvider->getWorkspace();

        $profile = $this->regulatoryProfileRepository->findOneByWorkspace(workspace: $workspace);

        if (!$profile instanceof \App\Domain\Firm\Entity\RegulatoryProfile) {
            throw ProfileNotFoundException::withWorkspaceName($workspace->name);
        }

        return RegulatoryProfilResponse::fromEntity($profile);
    }
}
