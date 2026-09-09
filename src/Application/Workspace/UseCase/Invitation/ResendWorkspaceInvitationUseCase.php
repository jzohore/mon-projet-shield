<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\Invitation;

use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Event\WorkspaceInvitationResentEvent;
use App\Domain\Workspace\Exception\InvitationAlreadyUsedException;
use App\Domain\Workspace\Exception\NotWorkspaceAdminException;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class ResendWorkspaceInvitationUseCase
{
    public function __construct(
        private WorkspaceInvitationRepositoryInterface $repository,
        private CurrentUserProvider $currentUserProvider,
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(WorkspaceInvitation $workspaceInvitation): void
    {
        $currentUser = $this->currentUserProvider->getUser();
        if (!$this->workspaceMemberRepository->isUserAdminOfWorkspace($currentUser, $workspaceInvitation->workspace)) {
            throw NotWorkspaceAdminException::create();
        }

        if (!$workspaceInvitation->isPending()) {
            throw InvitationAlreadyUsedException::create();
        }

        $workspaceInvitation->generateMagicLinkToken();
        $this->repository->save($workspaceInvitation);

        // L'e-mail (dispatch Messenger) et la trace d'audit sont câblés par des
        // listeners d'infrastructure : le use case reste dans sa couche.
        $this->eventDispatcher->dispatch(new WorkspaceInvitationResentEvent(
            $workspaceInvitation,
            $currentUser,
            $workspaceInvitation->workspace,
        ));
    }
}
