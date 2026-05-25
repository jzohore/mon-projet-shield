<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\Invitation;

use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Event\WorkspaceInvitationRevokeEvent;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

readonly class RevokeWorkspaceInvitationUseCase
{
    public function __construct(
        private WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(WorkspaceInvitation $workspaceInvitation): void
    {
        $user = $workspaceInvitation->owner;
        $workspace = $workspaceInvitation->workspace;
        $this->workspaceInvitationRepository->delete($workspaceInvitation);
        $this->eventDispatcher->dispatch(new WorkspaceInvitationRevokeEvent($workspaceInvitation, $user, $workspace));

    }
}
