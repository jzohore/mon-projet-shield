<?php

declare(strict_types=1);

namespace App\Tests\Application\Workspace\Invitation;

use App\Application\Workspace\UseCase\Invitation\ResendWorkspaceInvitationUseCase;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Enum\InvitationStatus;
use App\Domain\Workspace\Enum\InvitedRole;
use App\Domain\Workspace\Event\WorkspaceInvitationResentEvent;
use App\Domain\Workspace\Exception\InvitationAlreadyUsedException;
use App\Domain\Workspace\Exception\NotWorkspaceAdminException;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ResendWorkspaceInvitationUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private WorkspaceInvitationRepositoryInterface&MockObject $repository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private User $currentUser;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(WorkspaceInvitationRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->currentUser = $this->createEntityState(User::class, ['firstName' => 'Bob', 'lastName' => 'Admin', 'email' => 'bob@cabinet.fr']);
    }

    private function useCase(bool $isAdmin = true): ResendWorkspaceInvitationUseCase
    {
        $userProvider = $this->createStub(CurrentUserProvider::class);
        $userProvider->method('getUser')->willReturn($this->currentUser);

        $memberRepository = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $memberRepository->method('isUserAdminOfWorkspace')->willReturn($isAdmin);

        return new ResendWorkspaceInvitationUseCase(
            $this->repository,
            $userProvider,
            $memberRepository,
            $this->eventDispatcher,
        );
    }

    private function invitation(InvitationStatus $status = InvitationStatus::PENDING): WorkspaceInvitation
    {
        return $this->createEntityState(WorkspaceInvitation::class, [
            'slugId' => 'wrk_inv_1',
            'email' => 'collab@cabinet.fr',
            'firstName' => 'Marie',
            'lastName' => 'Curie',
            'invitedRole' => InvitedRole::ROLE_WORKSPACE_COLLAB,
            'invitationStatus' => $status,
            'workspace' => $this->createEntityState(Workspace::class, ['name' => 'Cabinet', 'slugId' => 'wrk_1']),
        ]);
    }

    public function testRegeneratesTheTokenAndDispatchesTheResentEvent(): void
    {
        $invitation = $this->invitation();

        $this->repository->expects($this->once())->method('save')->with($invitation);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(fn (object $event): bool => $event instanceof WorkspaceInvitationResentEvent
                && $event->resentBy === $this->currentUser
                && $event->workspaceInvitation === $invitation))
            ->willReturnArgument(0);

        ($this->useCase())($invitation);

        self::assertNotNull($invitation->plainMagicLinkToken);
    }

    public function testRejectsNonAdmin(): void
    {
        $this->repository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(NotWorkspaceAdminException::class);
        ($this->useCase(isAdmin: false))($this->invitation());
    }

    public function testRejectsInvitationThatIsNoLongerPending(): void
    {
        $this->repository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(InvitationAlreadyUsedException::class);
        ($this->useCase())($this->invitation(InvitationStatus::ACCEPTED));
    }
}
