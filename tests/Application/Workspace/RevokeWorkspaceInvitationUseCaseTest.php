<?php

namespace App\Tests\Application\Workspace;

use App\Application\Workspace\UseCase\Invitation\RevokeWorkspaceInvitationUseCase;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Enum\Industry;
use App\Domain\Workspace\Enum\InvitedRole; // À adapter selon ton code
use App\Domain\Workspace\Event\WorkspaceInvitationRevokeEvent;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RevokeWorkspaceInvitationUseCaseTest extends TestCase
{
    private WorkspaceInvitationRepositoryInterface|MockObject $repositoryMock;
    private EventDispatcherInterface|MockObject $eventDispatcherMock;
    private RevokeWorkspaceInvitationUseCase $useCase;

    protected function setUp(): void
    {
        // 1. On crée les Mocks des dépendances
        $this->repositoryMock = $this->createMock(WorkspaceInvitationRepositoryInterface::class);
        $this->eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);

        // 2. On instancie le Use Case
        $this->useCase = new RevokeWorkspaceInvitationUseCase(
            $this->repositoryMock,
            $this->eventDispatcherMock
        );
    }

    public function testItDeletesInvitationAndDispatchesEvent(): void
    {
        // --- ARRANGE ---
        $mockUser = clone User::create('admin@example.com', 'Admin', 'User');
        $mockWorkspace = clone Workspace::create('Tech Corp', '1234', 'Tech SAS', 'Paris', Industry::OTHER);

        // On utilise une vraie entité pour l'invitation afin d'avoir accès à ses propriétés publiques
        // (Adapte les paramètres de create() selon l'implémentation de ton entité)
        $invitation = WorkspaceInvitation::create(
            owner: $mockUser,
            workspace: $mockWorkspace,
            email: 'target@example.com',
            firstName: 'John',
            lastName: 'Doe',
            invitedRole: InvitedRole::ROLE_WORKSPACE_COLLAB
        );

        // --- EXPECT ---
        // 1. Le Repository DOIT être appelé pour supprimer cette invitation précise
        $this->repositoryMock
            ->expects($this->once())
            ->method('delete')
            ->with($invitation);

        // 2. L'EventDispatcher DOIT être appelé avec le bon événement
        $this->eventDispatcherMock
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (object $event) use ($invitation, $mockUser, $mockWorkspace) {
                // On vérifie que c'est le bon événement
                $this->assertInstanceOf(WorkspaceInvitationRevokeEvent::class, $event);

                // Si tu veux aller jusqu'au bout, tu peux même vérifier que l'événement
                // contient les bonnes données (si ton événement a des getters ou propriétés publiques)
                $this->assertSame($invitation, $event->workspaceInvitation);
                $this->assertSame($mockUser, $event->user);
                $this->assertSame($mockWorkspace, $event->workspace);

                return $event;
            });

        // --- ACT ---
        ($this->useCase)($invitation);
    }
}
