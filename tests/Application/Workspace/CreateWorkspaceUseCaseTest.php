<?php

declare(strict_types=1);

namespace App\Tests\Application\Workspace;

use App\Application\Workspace\DTO\Request\CreateWorkspaceRequest;
use App\Application\Workspace\UseCase\Onboarding\CreateWorkspaceUseCase;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\Firm\Entity\RegulatoryProfile;
use App\Domain\Firm\Repository\RegulatoryProfileRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Enum\Industry;
use App\Domain\Workspace\Event\WorkspaceCreatedEvent;
use App\Domain\Workspace\Exception\WorkspaceNameAlreadyExistsException;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class CreateWorkspaceUseCaseTest extends TestCase
{
    private WorkspaceRepositoryInterface&MockObject $workspaceRepository;
    private UserRepositoryInterface&MockObject $userRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private TransactionManagerInterface&MockObject $transactionManager;
    private WorkspaceMemberRepositoryInterface&MockObject $workspaceMemberRepository;
    private CurrentUserProvider&MockObject $currentUserProvider;
    private RegulatoryProfileRepositoryInterface&MockObject $regulatoryProfileRepository;
    private CreateWorkspaceUseCase $useCase;

    protected function setUp(): void
    {
        $this->workspaceRepository = $this->createMock(WorkspaceRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->transactionManager = $this->createMock(TransactionManagerInterface::class);
        $this->workspaceMemberRepository = $this->createMock(WorkspaceMemberRepositoryInterface::class);
        $this->currentUserProvider = $this->createMock(CurrentUserProvider::class);
        $this->regulatoryProfileRepository = $this->createMock(RegulatoryProfileRepositoryInterface::class);

        $this->useCase = new CreateWorkspaceUseCase(
            $this->workspaceRepository,
            $this->userRepository,
            $this->eventDispatcher,
            $this->transactionManager,
            $this->workspaceMemberRepository,
            $this->currentUserProvider,
            $this->regulatoryProfileRepository
        );
    }

    /**
     * Helper pour instancier un User sans dépendre de son constructeur.
     */
    private function createMockUser(): User
    {
        $reflection = new \ReflectionClass(User::class);
        $user = $reflection->newInstanceWithoutConstructor();

        $reflection->getProperty('email')->setValue($user, 'cgp@kysure.fr');
        $reflection->getProperty('onboardingStatus')->setValue($user, OnboardingStatus::PENDING);

        return $user;
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionIfWorkspaceNameAlreadyExists(): void
    {
        // Arrange
        $user = $this->createMockUser();
        $this->currentUserProvider->method('getUser')->willReturn($user);

        $request = new CreateWorkspaceRequest();
        $request->name = 'Cabinet Kysure';

        $this->workspaceRepository->expects($this->once())
            ->method('existsByName')
            ->with('Cabinet Kysure')
            ->willReturn(true);

        // Assert
        $this->expectException(WorkspaceNameAlreadyExistsException::class);

        // Act
        ($this->useCase)($request);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testSuccessfullyCreatesWorkspaceAndDispatchesEvent(): void
    {
        // Arrange
        $user = $this->createMockUser();
        $this->currentUserProvider->method('getUser')->willReturn($user);

        $request = new CreateWorkspaceRequest();
        $request->name = 'Cabinet Kysure';
        $request->legalName = 'KYSURE SAS';
        $request->address = '10 Rue de la Paix, Paris';
        $request->etatAdministratif = 'Actif';
        $request->workspaceIndustry = Industry::OTHER;
        // On suppose que Siret est optionnel ou non fourni dans ce cas précis

        $this->workspaceRepository->method('existsByName')->willReturn(false);

        // 🛡️ Magie du test : On force le TransactionManager à exécuter la Closure
        $this->transactionManager->expects($this->once())
            ->method('transactional')
            ->willReturnCallback(static function (callable $callback): void {
                $callback();
            });

        // Assertions sur la persistance (Vérification que les 4 dépôts sont appelés)
        $this->workspaceRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Workspace::class));

        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (User $savedUser): bool => OnboardingStatus::WORKSPACE_SETUP === $savedUser->onboardingStatus));

        $this->workspaceMemberRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(WorkspaceMember::class));

        $this->regulatoryProfileRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(RegulatoryProfile::class));

        // Assertions sur l'Event-Driven
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(WorkspaceCreatedEvent::class));

        // Act
        ($this->useCase)($request);
    }
}
