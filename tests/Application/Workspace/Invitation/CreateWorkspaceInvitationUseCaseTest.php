<?php

declare(strict_types=1);

namespace App\Tests\Application\Workspace\Invitation;

use App\Application\Workspace\DTO\Request\CreateWorkspaceInvitationRequest;
use App\Application\Workspace\UseCase\Invitation\CreateWorkspaceInvitationUseCase;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Enum\InvitedRole;
use App\Domain\Workspace\Event\WorkspaceInvitationCreatedEvent;
use App\Domain\Workspace\Exception\NotWorkspaceAdminException;
use App\Domain\Workspace\Exception\SeatLimitReachedException;
use App\Domain\Workspace\Exception\UserAlreadyBelongsToAnotherWorkspaceException;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Domain\Workspace\Service\SeatAvailability;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class CreateWorkspaceInvitationUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private WorkspaceInvitationRepositoryInterface&MockObject $invitationRepository;
    private WorkspaceMemberRepositoryInterface $memberRepository;
    private UserRepositoryInterface $userRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private Workspace $workspace;

    protected function setUp(): void
    {
        $this->workspace = $this->createEntityState(Workspace::class, ['name' => 'Cabinet', 'slugId' => 'wrk_1']);

        $this->invitationRepository = $this->createMock(WorkspaceInvitationRepositoryInterface::class);
        $this->memberRepository = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $this->userRepository = $this->createStub(UserRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    private function useCase(bool $seatFree, bool $isAdmin = true): CreateWorkspaceInvitationUseCase
    {
        $this->memberRepository->method('isUserAdminOfWorkspace')->willReturn($isAdmin);

        $userProvider = $this->createStub(CurrentUserProvider::class);
        $userProvider->method('getUser')->willReturn($this->createEntityState(User::class, [
            'firstName' => 'Jean', 'lastName' => 'Dupont', 'email' => 'jean@cabinet.fr',
        ]));
        $workspaceProvider = $this->createStub(CurrentWorkspaceProvider::class);
        $workspaceProvider->method('getWorkspace')->willReturn($this->workspace);

        $seatAvailability = $this->createStub(SeatAvailability::class);
        $seatAvailability->method('hasFreeSeat')->willReturn($seatFree);
        $seatAvailability->method('usedSeats')->willReturn(2);
        $seatAvailability->method('allowedSeats')->willReturn(2);

        return new CreateWorkspaceInvitationUseCase(
            $this->invitationRepository,
            $this->memberRepository,
            $this->userRepository,
            $this->eventDispatcher,
            $userProvider,
            $workspaceProvider,
            $seatAvailability,
        );
    }

    private function request(): CreateWorkspaceInvitationRequest
    {
        $request = new CreateWorkspaceInvitationRequest();
        $request->email = 'collab@cabinet.fr';
        $request->firstName = 'Marie';
        $request->lastName = 'Curie';
        $request->invitedRole = InvitedRole::ROLE_WORKSPACE_COLLAB;

        return $request;
    }

    public function testCreatesInvitationWhenASeatIsFree(): void
    {
        $this->invitationRepository->method('hasPendingInvitation')->willReturn(false);
        $this->memberRepository->method('isAlreadyMember')->willReturn(false);

        $this->invitationRepository->expects($this->once())->method('save');
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(WorkspaceInvitationCreatedEvent::class))
            ->willReturnArgument(0);

        ($this->useCase(seatFree: true))($this->request());
    }

    public function testRejectsInvitationWhenNoSeatIsLeft(): void
    {
        $this->invitationRepository->method('hasPendingInvitation')->willReturn(false);
        $this->memberRepository->method('isAlreadyMember')->willReturn(false);

        $this->invitationRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(SeatLimitReachedException::class);
        ($this->useCase(seatFree: false))($this->request());
    }

    public function testRejectsInvitationWhenEmailBelongsToAnotherWorkspace(): void
    {
        $this->invitationRepository->method('hasPendingInvitation')->willReturn(false);
        $this->memberRepository->method('isAlreadyMember')->willReturn(false);

        $existingUser = $this->createEntityState(User::class, ['email' => 'collab@cabinet.fr']);
        $otherWorkspace = $this->createEntityState(Workspace::class, ['name' => 'Autre', 'slugId' => 'wrk_other']);
        $foreignMembership = $this->createEntityState(WorkspaceMember::class, ['workspace' => $otherWorkspace]);

        $this->userRepository->method('findByEmail')->willReturn($existingUser);
        $this->memberRepository->method('findByUser')->willReturn([$foreignMembership]);

        $this->invitationRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(UserAlreadyBelongsToAnotherWorkspaceException::class);
        ($this->useCase(seatFree: true))($this->request());
    }

    public function testRejectsInvitationFromNonAdmin(): void
    {
        $this->invitationRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(NotWorkspaceAdminException::class);
        ($this->useCase(seatFree: true, isAdmin: false))($this->request());
    }
}
