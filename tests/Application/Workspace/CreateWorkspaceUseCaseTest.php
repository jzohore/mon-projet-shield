<?php

namespace App\Tests\Application\Workspace;

use App\Application\Workspace\DTO\Request\CreateWorkspaceRequest;
use App\Application\Workspace\DTO\Response\WorkspaceInfoResponse;
use App\Application\Workspace\UseCase\Onboarding\CreateWorkspaceUseCase;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Event\WorkspaceCreatedEvent;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CreateWorkspaceUseCaseTest extends TestCase
{
    private WorkspaceRepositoryInterface|MockObject $workspaceRepositoryMock;
    private UserRepositoryInterface|MockObject $userRepositoryMock;
    private EventDispatcherInterface|MockObject $eventDispatcherMock;
    private TransactionManagerInterface|MockObject $transactionManagerMock;
    private WorkspaceMemberRepositoryInterface|MockObject $workspaceMemberRepositoryMock;
    private CurrentUserProvider|MockObject $currentUserProviderMock;
    private CreateWorkspaceUseCase $useCase;

    protected function setUp(): void
    {
        $this->workspaceRepositoryMock = $this->createMock(WorkspaceRepositoryInterface::class);
        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $this->transactionManagerMock = $this->createMock(TransactionManagerInterface::class);
        $this->workspaceMemberRepositoryMock = $this->createMock(WorkspaceMemberRepositoryInterface::class);

        // Attention au bypass du mot-clé final/readonly si nécessaire,
        // ou utilise l'interface si tu en as créé une (CurrentUserProviderInterface)
        $this->currentUserProviderMock = $this->createMock(CurrentUserProvider::class);

        $this->useCase = new CreateWorkspaceUseCase(
            $this->workspaceRepositoryMock,
            $this->userRepositoryMock,
            $this->eventDispatcherMock,
            $this->transactionManagerMock,
            $this->workspaceMemberRepositoryMock,
            $this->currentUserProviderMock
        );
    }

    public function testItCreatesWorkspaceSuccessfully(): void
    {
        // --- ARRANGE ---
        $request = new CreateWorkspaceRequest();
        $request->name = 'Tech Corp';
        $request->siret = '12345678901234';
        $request->legalName = 'Tech Corp SAS';
        $request->address = '123 Rue de la Paix, Paris';
        // Note: Assigne une valeur valide pour $request->workspaceIndustry selon ton DTO/Enum

        // 🪄 On utilise un vrai User pour valider l'impact sur son onboardingStatus
        $realUser = clone User::create('test@example.com', 'John', 'Doe');

        $this->currentUserProviderMock
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($realUser);

        // 🛡️ LE PIÈGE ÉVITÉ : On force le mock à exécuter la closure (callable)
        $this->transactionManagerMock
            ->expects($this->once())
            ->method('transactional')
            ->willReturnCallback(function (callable $operation) {
                return $operation(); // On exécute le contenu du bloc !
            });

        // 1. On vérifie que le Workspace est sauvegardé
        $this->workspaceRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Workspace::class));

        // 2. On vérifie que le User est sauvegardé
        $this->userRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($realUser);

        // 3. On vérifie que le lien Membre/Admin est sauvegardé
        $this->workspaceMemberRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(WorkspaceMember::class));

        // On vérifie que l'événement est bien dispatché
        $this->eventDispatcherMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(WorkspaceCreatedEvent::class));

        // --- ACT ---
        $response = ($this->useCase)($request);

        // --- ASSERT ---
        // On s'assure que le UseCase a bien mis à jour le statut du vrai utilisateur
        $this->assertSame(OnboardingStatus::WORKSPACE_SETUP, $realUser->onboardingStatus);

        // On s'assure que le retour est bien le bon DTO
        $this->assertInstanceOf(WorkspaceInfoResponse::class, $response);
    }
}
