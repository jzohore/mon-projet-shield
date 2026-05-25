<?php

namespace App\Tests\Application\Workspace;

use App\Application\Workspace\UseCase\Invitation\ResendWorkspaceInvitationUseCase;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Enum\Industry;
use App\Domain\Workspace\Enum\InvitedRole;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Infrastructure\Workspace\Message\DispatchInvitationEmailMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

// À adapter

class ResendWorkspaceInvitationUseCaseTest extends TestCase
{
    private MessageBusInterface|MockObject $messageBusMock;
    private UrlGeneratorInterface|MockObject $routerMock;
    private ResendWorkspaceInvitationUseCase $useCase;

    private WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository;

    protected function setUp(): void
    {
        $this->messageBusMock = $this->createMock(MessageBusInterface::class);
        $this->routerMock = $this->createMock(UrlGeneratorInterface::class);
        $this->workspaceInvitationRepository = $this->createMock(WorkspaceInvitationRepositoryInterface::class);

        $this->useCase = new ResendWorkspaceInvitationUseCase(
            $this->messageBusMock,
            $this->routerMock,
            $this->workspaceInvitationRepository,
        );
    }

    public function testItRegeneratesTokenGeneratesUrlAndDispatchesMessage(): void
    {
        // --- ARRANGE ---
        $mockUser = clone User::create('admin@example.com', 'Admin', 'User');
        $mockWorkspace = clone Workspace::create('Tech Corp', '1234', 'Tech SAS', 'Paris', Industry::OTHER);

        $invitation = WorkspaceInvitation::create(
            owner: $mockUser,
            workspace: $mockWorkspace,
            email: 'invite@example.com',
            firstName: 'John',
            lastName: 'Doe',
            invitedRole: InvitedRole::ROLE_WORKSPACE_COLLAB
        );
        $reflection = new \ReflectionClass($invitation);
        $idProperty = $reflection->getProperty('id');
        // Utilise Uuid::v4() ou Ramsey\Uuid\Uuid::uuid4() selon ta surcouche d'UUID
        $idProperty->setValue($invitation, \Symfony\Component\Uid\Uuid::v4());
        // On lui met un token initial pour s'assurer que clear + generate fonctionnent
        $invitation->generateMagicLinkToken();
        $this->workspaceInvitationRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(WorkspaceInvitation::class));

        $oldToken = $invitation->magicLinkToken;

        // 🪄 Configuration du Mock du Routeur
        // On s'attend à ce qu'il génère l'URL absolue 'http://localhost/confirm-token'
        $expectedUrl = 'http://localhost/confirm-token';
        $this->routerMock
            ->expects($this->once())
            ->method('generate')
            ->with(
                'portal_user_confirm_token',
                // callback permettant d'accepter n'importe quel token généré à la volée
                $this->callback(function (array $params) use ($oldToken) {
                    return isset($params['token']) && $params['token'] !== $oldToken;
                }),
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn($expectedUrl);

        // 🛡️ L'ASTUCE DU MESSAGE BUS :
        // dispatch() DOIT renvoyer une Envelope contenant le message sous peine de Fatal Error PHP
        $this->messageBusMock
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (object $message) use ($invitation, $expectedUrl) {
                // On intercepte le message pour valider ses données internes
                $this->assertInstanceOf(DispatchInvitationEmailMessage::class, $message);

                // On vérifie que l'ID de l'invitation et l'URL générée sont corrects
                $this->assertSame($invitation->id->toString(), $message->invitationId); // Adapte le getter du message si besoin
                $this->assertSame($expectedUrl, $message->url); // Adapte le getter du message si besoin

                // On emballe le message dans une enveloppe de test
                return new Envelope($message);
            });

        // --- ACT ---
        ($this->useCase)($invitation);

        // --- ASSERT ---
        // On s'assure qu'un nouveau token a bien été généré et qu'il est différent de l'ancien
        $this->assertNotEmpty($invitation->magicLinkToken);
    }
}
