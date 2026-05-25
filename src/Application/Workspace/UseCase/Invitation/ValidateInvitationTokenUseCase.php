<?php

namespace App\Application\Workspace\UseCase\Invitation;

use App\Application\Workspace\DTO\Response\WorkspaceInvitationInfoResponse;
use App\Domain\Workspace\Exception\InvitationTokenNotFoundException;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;

readonly class ValidateInvitationTokenUseCase
{
    public function __construct(
        private WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
    ) {}

    public function __invoke(string $shareToken): WorkspaceInvitationInfoResponse
    {
        $invitation = $this->workspaceInvitationRepository->findByToken($shareToken);

        if ($invitation === null || !$invitation->isMagicLinkTokenValid()) {
            throw InvitationTokenNotFoundException::withToken($shareToken);
        }

        return WorkspaceInvitationInfoResponse::fromEntity($invitation);
    }
}
