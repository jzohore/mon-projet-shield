<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\Invitation;

use App\Application\Workspace\DTO\Response\WorkspaceInvitationInfoResponse;
use App\Domain\Workspace\Exception\InvitationNotFoundException;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;

readonly class GetCurrentInvitationUseCase
{
    public function __construct(
        private WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
    ) {
    }

    public function __invoke(string $slugId): WorkspaceInvitationInfoResponse
    {
        $invitation = $this->workspaceInvitationRepository->findBySlugId($slugId);

        if (!$invitation instanceof \App\Domain\Workspace\Entity\WorkspaceInvitation) {
            throw InvitationNotFoundException::withSlugId($slugId);
        }

        return WorkspaceInvitationInfoResponse::fromEntity($invitation);
    }
}
