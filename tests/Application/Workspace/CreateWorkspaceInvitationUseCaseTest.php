<?php

namespace App\Tests\Application\Workspace;

use App\Application\Workspace\DTO\Request\CreateWorkspaceInvitationRequest;
use App\Application\Workspace\UseCase\Invitation\CreateWorkspaceInvitationUseCase;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Enum\Industry;
use App\Domain\Workspace\Enum\InvitedRole;
use App\Domain\Workspace\Event\WorkspaceInvitationCreatedEvent;
use App\Domain\Workspace\Exception\HasPendingInvitationException;
use App\Domain\Workspace\Exception\IsAlreadyMemberException;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

// À adapter

class CreateWorkspaceInvitationUseCaseTest extends TestCase
{
    private WorkspaceInvitationRepositoryInterface|MockObject $invitationRepositoryMock;
    private WorkspaceMemberRepositoryInterface|MockObject $memberRepositoryMock;
    private EventDispatcherInterface|MockObject $eventDispatcherMock;
    private CurrentUserProvider|MockObject $currentUserProviderMock;
    private CurrentWorkspaceProvider|MockObject $currentWorkspaceProviderMock;

    private CreateWorkspaceInvitationUseCase $useCase;

    private User $mockUser;
    private Workspace $mockWorkspace;
    private CreateWorkspaceInvitationRequest $request;

    protected function setUp(): void
    {
        // 1. Création des Mocks
        $this->invitationRepositoryMock = $this->createMock(WorkspaceInvitationRepositoryInterface::class);
        $this->memberRepositoryMock = $this->createMock(WorkspaceMemberRepositoryInterface::class);
        $this->eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $this->currentUserProviderMock = $this->createMock(CurrentUserProvider::class);
        $this->currentWorkspaceProviderMock = $this->createMock(CurrentWorkspaceProvider::class);

        // 2. Instanciation du Use Case
        $this->useCase = new CreateWorkspaceInvitationUseCase(
            $this->invitationRepositoryMock,
            $this->memberRepositoryMock,
            $this->eventDispatcherMock,
            $this->currentUserProviderMock,
            $this->currentWorkspaceProviderMock
        );

        // 3. Préparation des données communes à tous les tests
        $this->mockUser = clone User::create('admin@example.com', 'Admin', 'User');
        $this->mockWorkspace = clone Workspace::create('Tech Corp', '1234', 'Tech SAS', 'Paris', Industry::OTHER);

        $this->request = new CreateWorkspaceInvitationRequest();
        $this->request->email = 'invite@example.com';
        $this->request->firstName = 'John';
        $this->request->lastName = 'Doe';
        $this->request->invitedRole = InvitedRole::ROLE_WORKSPACE_COLLAB;

        // On configure les providers pour qu'ils renvoient toujours nos fausses entités
        $this->currentUserProviderMock->method('getUser')->willReturn($this->mockUser);
        $this->currentWorkspaceProviderMock->method('getWorkspace')->willReturn($this->mockWorkspace);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testItCreatesAndSavesInvitationSuccessfully(): void
    {
        // --- ARRANGE ---
        // Les deux gardes doivent renvoyer 'false' pour laisser passer
        $this->invitationRepositoryMock
            ->expects($this->once())
            ->method('hasPendingInvitation')
            ->with($this->mockWorkspace, $this->request->email)
            ->willReturn(false);

        $this->memberRepositoryMock
            ->expects($this->once())
            ->method('isAlreadyMember')
            ->with($this->mockWorkspace, $this->request->email)
            ->willReturn(false);

        // --- EXPECT ---
        // On s'attend à ce que l'invitation soit sauvegardée
        $this->invitationRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(WorkspaceInvitation::class));

        // On s'attend à ce que l'événement soit dispatché
        $this->eventDispatcherMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(WorkspaceInvitationCreatedEvent::class));

        // --- ACT ---
        ($this->useCase)($this->request);

        // (Les assertions sont gérées par les expects ci-dessus)
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testItThrowsExceptionIfInvitationIsPending(): void
    {
        // --- ARRANGE & EXPECT ---
        // Le premier garde renvoie 'true'
        $this->invitationRepositoryMock
            ->expects($this->once())
            ->method('hasPendingInvitation')
            ->with($this->mockWorkspace, $this->request->email)
            ->willReturn(true);

        // Le UseCase doit s'arrêter là ! On s'assure que le reste n'est JAMAIS appelé
        $this->memberRepositoryMock->expects($this->never())->method('isAlreadyMember');
        $this->invitationRepositoryMock->expects($this->never())->method('save');
        $this->eventDispatcherMock->expects($this->never())->method('dispatch');

        // On indique à PHPUnit qu'on s'attend à recevoir cette exception précise
        $this->expectException(HasPendingInvitationException::class);

        // --- ACT ---
        ($this->useCase)($this->request);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testItThrowsExceptionIfUserIsAlreadyMember(): void
    {
        // --- ARRANGE & EXPECT ---
        // Le premier garde passe
        $this->invitationRepositoryMock
            ->expects($this->once())
            ->method('hasPendingInvitation')
            ->willReturn(false);

        // Le deuxième garde bloque (renvoie 'true')
        $this->memberRepositoryMock
            ->expects($this->once())
            ->method('isAlreadyMember')
            ->with($this->mockWorkspace, $this->request->email)
            ->willReturn(true);

        // On s'assure qu'on ne sauvegarde rien et qu'on n'envoie pas d'événement
        $this->invitationRepositoryMock->expects($this->never())->method('save');
        $this->eventDispatcherMock->expects($this->never())->method('dispatch');

        // On s'attend à cette exception
        $this->expectException(IsAlreadyMemberException::class);

        // --- ACT ---
        ($this->useCase)($this->request);
    }
}
