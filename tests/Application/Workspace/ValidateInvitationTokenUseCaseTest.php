<?php

namespace App\Tests\Application\Workspace;

use App\Application\Workspace\DTO\Response\WorkspaceInvitationInfoResponse;
use App\Application\Workspace\UseCase\Invitation\ValidateInvitationTokenUseCase;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Enum\Industry;
use App\Domain\Workspace\Enum\InvitedRole;
use App\Domain\Workspace\Exception\InvitationTokenNotFoundException;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidateInvitationTokenUseCaseTest extends TestCase
{
    private WorkspaceInvitationRepositoryInterface|MockObject $repositoryMock;
    private ValidateInvitationTokenUseCase $useCase;

    protected function setUp(): void
    {
        // 1. Mock du Repository
        $this->repositoryMock = $this->createMock(WorkspaceInvitationRepositoryInterface::class);

        // 2. Instanciation du Use Case
        $this->useCase = new ValidateInvitationTokenUseCase($this->repositoryMock);
    }

    public function testItThrowsExceptionWhenInvitationIsNotFound(): void
    {
        // --- ARRANGE ---
        $fakeToken = 'token-qui-n-existe-pas';

        $this->repositoryMock
            ->expects($this->once())
            ->method('findByToken')
            ->with($fakeToken)
            ->willReturn(null); // Le repository ne trouve rien

        // --- EXPECT ---
        $this->expectException(InvitationTokenNotFoundException::class);

        // --- ACT ---
        ($this->useCase)($fakeToken);
    }

    public function testItThrowsExceptionWhenTokenIsInvalidOrExpired(): void
    {
        // --- ARRANGE ---
        $expiredToken = 'token-expire';

        // 🪄 On mock l'entité pour forcer son comportement sans s'embêter avec les dates
        $invitationMock = $this->createMock(WorkspaceInvitation::class);
        $invitationMock
            ->expects($this->once())
            ->method('isMagicLinkTokenValid')
            ->willReturn(false); // On simule un token expiré

        $this->repositoryMock
            ->expects($this->once())
            ->method('findByToken')
            ->with($expiredToken)
            ->willReturn($invitationMock);

        // --- EXPECT ---
        $this->expectException(InvitationTokenNotFoundException::class);

        // --- ACT ---
        ($this->useCase)($expiredToken);
    }

    public function testItReturnsResponseWhenTokenIsValid(): void
    {
        // --- ARRANGE ---
        $validToken = 'super-token-valide';

        // Pour le succès, on a besoin d'une vraie entité pour que le DTO
        // puisse lire les propriétés correctement via fromEntity()
        $mockUser = clone User::create('admin@example.com', 'Admin', 'User');
        $mockWorkspace = clone Workspace::create('Tech Corp', '1234', 'Tech', 'Paris', Industry::OTHER);

        $invitation = WorkspaceInvitation::create(
            owner: $mockUser,
            workspace: $mockWorkspace,
            email: 'invite@example.com',
            firstName: 'John',
            lastName: 'Doe',
            invitedRole: InvitedRole::ROLE_WORKSPACE_COLLAB
        );

        // On s'assure que l'entité a un token valide (adapte selon ta méthode)
        $invitation->generateMagicLinkToken();

        $this->repositoryMock
            ->expects($this->once())
            ->method('findByToken')
            ->with($validToken)
            ->willReturn($invitation);

        // --- ACT ---
        $response = ($this->useCase)($validToken);

        // --- ASSERT ---
        // On vérifie que le Use Case nous a bien renvoyé notre DTO !
        $this->assertInstanceOf(WorkspaceInvitationInfoResponse::class, $response);

        // Tu peux même vérifier que le DTO contient la bonne data
        $this->assertSame('invite@example.com', $response->email);
    }
}
