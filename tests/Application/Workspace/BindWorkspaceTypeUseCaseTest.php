<?php

namespace App\Tests\Application\Workspace;

use App\Application\Workspace\UseCase\Onboarding\BindWorkspaceTypeUseCase;
use App\Domain\Billing\Event\CreateBillingModeEvent;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Event\UserOnboardingCompletedEvent;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Enum\Industry;
use App\Domain\Workspace\Enum\WorkspaceType;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BindWorkspaceTypeUseCaseTest extends TestCase
{
    private WorkspaceRepositoryInterface|MockObject $workspaceRepositoryMock;
    private UserRepositoryInterface|MockObject $userRepositoryMock;
    private EventDispatcherInterface|MockObject $eventDispatcherMock;
    private TransactionManagerInterface|MockObject $transactionManagerMock; // 🛡️
    private BindWorkspaceTypeUseCase $useCase;

    protected function setUp(): void
    {
        $this->workspaceRepositoryMock = $this->createMock(WorkspaceRepositoryInterface::class);
        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $this->transactionManagerMock = $this->createMock(TransactionManagerInterface::class); // 🛡️

        $this->useCase = new BindWorkspaceTypeUseCase(
            $this->workspaceRepositoryMock,
            $this->userRepositoryMock,
            $this->eventDispatcherMock,
            $this->transactionManagerMock // 🛡️
        );
    }

    public function testItBindsWorkspaceTypeWithTransactionAndCompletesOnboarding(): void
    {
        // --- ARRANGE ---
        $realUser = clone User::create('test@example.com', 'John', 'Doe');
        $realWorkspace = clone Workspace::create('Tech Corp', '12345678901234', 'Tech SAS', 'Paris', Industry::OTHER);
        $workspaceType = WorkspaceType::INDIVIDUAL;

        // 🪄 MAGIE DU TEST : On intercepte la closure de transaction et on FORCE son exécution
        $this->transactionManagerMock
            ->expects($this->once())
            ->method('transactional')
            ->willReturnCallback(function (callable $operation) {
                return $operation(); // On exécute le contenu du bloc !
            });

        // Les sauvegardes doivent avoir lieu (à l'intérieur de la transaction)
        $this->workspaceRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($realWorkspace);

        $this->userRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($realUser);

        // On intercepte les deux événements distincts
        $this->eventDispatcherMock
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (object $event) {
                $this->assertTrue(
                    $event instanceof UserOnboardingCompletedEvent
                    || $event instanceof CreateBillingModeEvent,
                    'Unexpected event dispatched: ' . get_class($event)
                );
                return $event;
            });

        // --- ACT ---
        ($this->useCase)($workspaceType, $realUser, $realWorkspace);

        // --- ASSERT ---
        $this->assertSame(OnboardingStatus::PLAN_SETUP, $realUser->onboardingStatus);
        $this->assertTrue($realWorkspace->isActive);
    }
}
