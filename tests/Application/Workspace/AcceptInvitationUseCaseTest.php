<?php

namespace App\Tests\Application\Workspace;

use App\Application\Workspace\UseCase\Invitation\AcceptInvitationUseCase;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Enum\Industry;
use App\Domain\Workspace\Enum\InvitedRole; // À adapter selon ton code
use App\Domain\Workspace\Exception\InvitationNotFoundException;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AcceptInvitationUseCaseTest extends TestCase
{
    private WorkspaceInvitationRepositoryInterface|MockObject $invitationRepositoryMock;
    private UserRepositoryInterface|MockObject $userRepositoryMock;
    private WorkspaceMemberRepositoryInterface|MockObject $memberRepositoryMock;
    private TransactionManagerInterface|MockObject $transactionManagerMock;

    private AcceptInvitationUseCase $useCase;

    protected function setUp(): void
    {
        // 1. Mocks des dépendances
        $this->invitationRepositoryMock = $this->createMock(WorkspaceInvitationRepositoryInterface::class);
        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->memberRepositoryMock = $this->createMock(WorkspaceMemberRepositoryInterface::class);
        $this->transactionManagerMock = $this->createMock(TransactionManagerInterface::class);

        // 2. Instanciation du Use Case
        $this->useCase = new AcceptInvitationUseCase(
            $this->invitationRepositoryMock,
            $this->userRepositoryMock,
            $this->memberRepositoryMock,
            $this->transactionManagerMock
        );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testItThrowsExceptionIfInvitationNotFound(): void
    {
        // --- ARRANGE ---
        $slug = 'slug-inconnu';

        $this->invitationRepositoryMock
            ->expects($this->once())
            ->method('findBySlugId')
            ->with($slug)
            ->willReturn(null);

        // --- EXPECT ---
        $this->expectException(InvitationNotFoundException::class);

        // --- ACT ---
        ($this->useCase)($slug);
    }

    public function testItAcceptsInvitationCreatesUserAndSavesInTransaction(): void
    {
        // --- ARRANGE ---
        $slug = 'valid-slug';

        $mockWorkspace = clone Workspace::create('Tech Corp', '1234', 'Tech', 'Paris', Industry::OTHER);

        // 🪄 L'Assert du UseCase vérifie que le workspace a un slugId.
        // S'il n'est pas initialisé à la création, on force sa valeur avec la Réflexion.
        $reflection = new \ReflectionClass($mockWorkspace);
        $slugIdProperty = $reflection->getProperty('slugId');
        $slugIdProperty->setValue($mockWorkspace, 'ws-slug-123');

        $mockOwner = clone User::create('admin@example.com', 'Admin', 'User');
        $invitation = WorkspaceInvitation::create(
            owner: $mockOwner,
            workspace: $mockWorkspace,
            email: 'newbie@example.com',
            firstName: 'John',
            lastName: 'Doe',
            invitedRole: InvitedRole::ROLE_WORKSPACE_COLLAB
        );
        $invitation->generateMagicLinkToken(); // On lui donne un token pour vérifier qu'il sera effacé

        $this->invitationRepositoryMock
            ->expects($this->once())
            ->method('findBySlugId')
            ->with($slug)
            ->willReturn($invitation);

        // 🛡️ L'ASTUCE MAGIQUE DE LA TRANSACTION :
        // On demande au mock d'exécuter la Closure qui lui est passée en argument
        $this->transactionManagerMock
            ->expects($this->once())
            ->method('transactional')
            ->willReturnCallback(function (callable $action) {
                // On exécute la fonction anonyme du Use Case !
                $action();
            });

        // --- EXPECT (Dans la transaction) ---
        // 1. Sauvegarde du User (avec flush = false)
        $this->userRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class), false);

        // 2. Sauvegarde de l'invitation (avec flush = false)
        $this->invitationRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($invitation, false);

        // 3. Sauvegarde du WorkspaceMember (avec flush = false)
        $this->memberRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(WorkspaceMember::class), false);

        // --- ACT ---
        $createdUser = ($this->useCase)($slug);

        // --- ASSERT ---
        // Vérification de l'utilisateur retourné
        $this->assertInstanceOf(User::class, $createdUser);
        $this->assertSame('newbie@example.com', $createdUser->email);
        $this->assertSame('John', $createdUser->firstName);
        $this->assertSame(OnboardingStatus::COMPLETED, $createdUser->onboardingStatus);

        // Vérification des mutations de l'invitation
        $this->assertNull($invitation->magicLinkToken);
        // $this->assertSame(InvitationStatus::ACCEPTED, $invitation->invitationStatus); // Si la propriété est publique ou via un getter
    }
}
