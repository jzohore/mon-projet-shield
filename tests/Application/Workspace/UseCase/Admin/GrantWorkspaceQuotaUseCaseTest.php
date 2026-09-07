<?php

declare(strict_types=1);

namespace App\Tests\Application\Workspace\UseCase\Admin;

use App\Application\Workspace\UseCase\Admin\GrantWorkspaceQuotaUseCase;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Event\WorkspaceQuotaGrantedEvent;
use App\Domain\Workspace\Exception\WorkspaceNotFoundException;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class GrantWorkspaceQuotaUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private WorkspaceRepositoryInterface&MockObject $workspaceRepository;
    private TransactionManagerInterface $transactionManager;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private GrantWorkspaceQuotaUseCase $useCase;

    protected function setUp(): void
    {
        $this->workspaceRepository = $this->createMock(WorkspaceRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        // Le TransactionManager n'est pas vérifié : on le stub pour exécuter la Closure.
        $this->transactionManager = $this->createStub(TransactionManagerInterface::class);
        $this->transactionManager->method('transactional')
            ->willReturnCallback(static fn (callable $callback) => $callback());

        $this->useCase = new GrantWorkspaceQuotaUseCase(
            $this->workspaceRepository,
            $this->transactionManager,
            $this->eventDispatcher,
        );
    }

    private function workspaceState(int $trialDossiersRemaining = 0, int $meetingMinutesAllocated = 0): Workspace
    {
        return $this->createEntityState(Workspace::class, [
            'slugId' => 'wrk_abc123',
            'trialDossiersRemaining' => $trialDossiersRemaining,
            'meetingMinutesAllocated' => $meetingMinutesAllocated,
            'meetingSecondsConsumed' => 0,
            'subscription' => null,
        ]);
    }

    public function testGrantsDossiersAndMinutesThenDispatchesEvent(): void
    {
        $workspace = $this->workspaceState(trialDossiersRemaining: 2, meetingMinutesAllocated: 90);
        $this->workspaceRepository->expects($this->once())
            ->method('findOneBySlug')->with('wrk_abc123')->willReturn($workspace);
        $this->workspaceRepository->expects($this->once())->method('save')->with($workspace);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (WorkspaceQuotaGrantedEvent $e): bool => 'wrk_abc123' === $e->workspaceSlugId
                && 3 === $e->dossiersGranted
                && 300 === $e->minutesGranted
                && 5 === $e->trialDossiersRemaining
                && 390 === $e->remainingMinutes
                && 'Geste commercial' === $e->reason
                && 'Admin KYSURE' === $e->actorName))
            ->willReturnArgument(0);

        ($this->useCase)('wrk_abc123', 3, 300, '  Geste commercial  ', 'Admin KYSURE', 'adm_1');

        self::assertSame(5, $workspace->trialDossiersRemaining);
        self::assertSame(390, $workspace->meetingMinutesAllocated);
    }

    public function testGrantsMinutesOnlyWithoutTouchingDossiers(): void
    {
        $workspace = $this->workspaceState(trialDossiersRemaining: 1, meetingMinutesAllocated: 0);
        $this->workspaceRepository->expects($this->once())->method('findOneBySlug')->willReturn($workspace);
        $this->eventDispatcher->expects($this->once())->method('dispatch')->willReturnArgument(0);

        ($this->useCase)('wrk_abc123', 0, 600, 'Pack offert', 'Admin KYSURE', 'adm_1');

        self::assertSame(1, $workspace->trialDossiersRemaining);
        self::assertSame(600, $workspace->meetingMinutesAllocated);
    }

    public function testNegativeAmountsAreClampedToZero(): void
    {
        $workspace = $this->workspaceState(trialDossiersRemaining: 0, meetingMinutesAllocated: 10);
        $this->workspaceRepository->expects($this->once())->method('findOneBySlug')->willReturn($workspace);
        $this->eventDispatcher->expects($this->once())->method('dispatch')->willReturnArgument(0);

        ($this->useCase)('wrk_abc123', -5, 120, 'Correction', 'Admin KYSURE', 'adm_1');

        self::assertSame(0, $workspace->trialDossiersRemaining);
        self::assertSame(130, $workspace->meetingMinutesAllocated);
    }

    public function testRejectsZeroDossiersAndZeroMinutes(): void
    {
        $this->workspaceRepository->expects($this->never())->method('findOneBySlug');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);
        ($this->useCase)('wrk_abc123', 0, 0, 'Rien', 'Admin KYSURE', 'adm_1');
    }

    public function testRejectsBlankReason(): void
    {
        $this->workspaceRepository->expects($this->never())->method('findOneBySlug');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);
        ($this->useCase)('wrk_abc123', 3, 0, '   ', 'Admin KYSURE', 'adm_1');
    }

    public function testThrowsWhenWorkspaceIsUnknown(): void
    {
        $this->workspaceRepository->expects($this->once())->method('findOneBySlug')->willReturn(null);
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(WorkspaceNotFoundException::class);
        ($this->useCase)('wrk_missing', 3, 0, 'Geste', 'Admin KYSURE', 'adm_1');
    }
}
