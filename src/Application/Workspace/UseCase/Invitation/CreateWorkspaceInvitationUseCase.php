<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\Invitation;

use App\Application\Workspace\DTO\Request\CreateWorkspaceInvitationRequest;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Event\WorkspaceInvitationCreatedEvent;
use App\Domain\Workspace\Exception\HasPendingInvitationException;
use App\Domain\Workspace\Exception\IsAlreadyMemberException;
use App\Domain\Workspace\Exception\NotWorkspaceAdminException;
use App\Domain\Workspace\Exception\SeatLimitReachedException;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Domain\Workspace\Service\SeatAvailability;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class CreateWorkspaceInvitationUseCase
{
    public function __construct(
        private WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private EventDispatcherInterface $eventDispatcher,
        private CurrentUserProvider $currentUserProvider,
        private CurrentWorkspaceProvider $currentWorkspaceProvider,
        private SeatAvailability $seatAvailability,
    ) {
    }

    public function __invoke(CreateWorkspaceInvitationRequest $request): void
    {
        $user = $this->currentUserProvider->getUser();
        $workspace = $this->currentWorkspaceProvider->getWorkspace();

        if (!$this->workspaceMemberRepository->isUserAdminOfWorkspace($user, $workspace)) {
            throw NotWorkspaceAdminException::create();
        }

        if ($this->workspaceInvitationRepository->hasPendingInvitation($workspace, $request->email)) {
            throw HasPendingInvitationException::withWorkspaceAndEmail(workspace: $workspace, email: $request->email);
        }

        if ($this->workspaceMemberRepository->isAlreadyMember($workspace, $request->email)) {
            throw IsAlreadyMemberException::withWorkspaceAndEmail(workspace: $workspace, email: $request->email);
        }

        if (!$this->seatAvailability->hasFreeSeat($workspace)) {
            throw SeatLimitReachedException::forWorkspace(usedSeats: $this->seatAvailability->usedSeats($workspace), allowedSeats: $this->seatAvailability->allowedSeats($workspace));
        }

        $newInvitation = WorkspaceInvitation::create(
            owner: $user,
            workspace: $workspace,
            email: $request->email,
            firstName: $request->firstName,
            lastName: $request->lastName,
            invitedRole: $request->invitedRole,
        );

        $this->workspaceInvitationRepository->save($newInvitation);
        $this->eventDispatcher->dispatch(new WorkspaceInvitationCreatedEvent(
            workspaceInvitation: $newInvitation,
            workspace: $workspace,
            user: $user,
        ));
    }
}
