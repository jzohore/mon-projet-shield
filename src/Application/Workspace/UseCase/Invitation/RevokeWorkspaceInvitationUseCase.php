<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\Invitation;

use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Enum\InvitationRevocationReason;
use App\Domain\Workspace\Event\WorkspaceInvitationRevokeEvent;
use App\Domain\Workspace\Exception\InvitationAlreadyUsedException;
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

    public function __invoke(WorkspaceInvitation $workspaceInvitation, InvitationRevocationReason $reason): void
    {
        $currentUser = $this->currentUserProvider->getUser();
        if (!$this->workspaceMemberRepository->isUserAdminOfWorkspace($currentUser, $workspaceInvitation->workspace)) {
            throw NotWorkspaceAdminException::create();
        }

        // 🛡️ On n'annule qu'une invitation encore en attente. Une invitation déjà
        // acceptée correspond à un accès réel : il se retire via la révocation de
        // membre, jamais en supprimant la ligne d'invitation (le journal mentirait).
        if (!$workspaceInvitation->isPending()) {
            throw InvitationAlreadyUsedException::create();
        }

        $workspace = $workspaceInvitation->workspace;

        $this->workspaceInvitationRepository->delete($workspaceInvitation);
        $this->eventDispatcher->dispatch(new WorkspaceInvitationRevokeEvent($workspaceInvitation, $currentUser, $workspace, $reason));
    }
}
