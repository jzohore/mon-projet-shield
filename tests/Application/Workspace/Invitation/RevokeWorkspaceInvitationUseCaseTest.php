<?php

declare(strict_types=1);

namespace App\Tests\Application\Workspace\Invitation;

use App\Application\Workspace\UseCase\Invitation\RevokeWorkspaceInvitationUseCase;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Enum\InvitationStatus;
use App\Domain\Workspace\Event\WorkspaceInvitationRevokeEvent;
use App\Domain\Workspace\Exception\InvitationAlreadyUsedException;
use App\Domain\Workspace\Exception\NotWorkspaceAdminException;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

final class RevokeWorkspaceInvitationUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private WorkspaceInvitationRepositoryInterface&MockObject $invitationRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private User $currentUser;

    protected function setUp(): void
    {
        $this->invitationRepository = $this->createMock(WorkspaceInvitationRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->currentUser = $this->createEntityState(User::class, ['firstName' => 'Bob', 'lastName' => 'Admin', 'email' => 'bob@cabinet.fr']);
    }

    private function useCase(bool $isAdmin = true): RevokeWorkspaceInvitationUseCase
    {
        $userProvider = $this->createStub(CurrentUserProvider::class);
        $userProvider->method('getUser')->willReturn($this->currentUser);

        $memberRepository = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $memberRepository->method('isUserAdminOfWorkspace')->willReturn($isAdmin);

        return new RevokeWorkspaceInvitationUseCase(
            $this->invitationRepository,
            $this->eventDispatcher,
            $userProvider,
            $memberRepository,
        );
    }

    private function invitation(InvitationStatus $status = InvitationStatus::PENDING): WorkspaceInvitation
    {
        $workspace = $this->createEntityState(Workspace::class, ['name' => 'Cabinet', 'slugId' => 'wrk_1']);
        $owner = $this->createEntityState(User::class, ['email' => 'owner@cabinet.fr']);

        return $this->createEntityState(WorkspaceInvitation::class, [
            'slugId' => 'wrk_inv_1',
            'email' => 'collab@cabinet.fr',
            'invitationStatus' => $status,
            'workspace' => $workspace,
            'owner' => $owner,
        ]);
    }

    public function testRevokesPendingInvitationAndAttributesTheActionToTheCurrentAdmin(): void
    {
        $invitation = $this->invitation();

        $this->invitationRepository->expects($this->once())->method('delete')->with($invitation);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(fn (object $event): bool => $event instanceof WorkspaceInvitationRevokeEvent
                && $event->revokedBy === $this->currentUser
                && $event->workspaceInvitation === $invitation))
            ->willReturnArgument(0);

        ($this->useCase())($invitation);
    }

    public function testRejectsNonAdmin(): void
    {
        $this->invitationRepository->expects($this->never())->method('delete');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(NotWorkspaceAdminException::class);
        ($this->useCase(isAdmin: false))($this->invitation());
    }

    public function testRejectsInvitationThatIsNoLongerPending(): void
    {
        $this->invitationRepository->expects($this->never())->method('delete');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(InvitationAlreadyUsedException::class);
        ($this->useCase())($this->invitation(InvitationStatus::ACCEPTED));
    }
}
