<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\Invitation;

use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Event\WorkspaceInvitationRevokeEvent;
use App\Domain\Workspace\Exception\NotWorkspaceAdminException;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use Psr\EventDispatcher\EventDispatcherInterface;

readonly class RevokeWorkspaceInvitationUseCase
{
    public function __construct(
        private WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
        private EventDispatcherInterface $eventDispatcher,
        private CurrentUserProvider $currentUserProvider,
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
    ) {
    }

    public function __invoke(WorkspaceInvitation $workspaceInvitation): void
    {
        $currentUser = $this->currentUserProvider->getUser();
        if (!$this->workspaceMemberRepository->isUserAdminOfWorkspace($currentUser, $workspaceInvitation->workspace)) {
            throw NotWorkspaceAdminException::create();
        }

        $owner = $workspaceInvitation->owner;
        $workspace = $workspaceInvitation->workspace;

        $this->workspaceInvitationRepository->delete($workspaceInvitation);
        $this->eventDispatcher->dispatch(new WorkspaceInvitationRevokeEvent($workspaceInvitation, $owner, $workspace));
    }
}
