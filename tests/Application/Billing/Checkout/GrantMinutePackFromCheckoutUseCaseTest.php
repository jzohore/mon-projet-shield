<?php

declare(strict_types=1);

namespace App\Tests\Application\Billing\Checkout;

use App\Application\Billing\UseCase\Checkout\GrantMinutePackFromCheckoutUseCase;
use App\Domain\Billing\Event\MinutePackPurchasedEvent;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

final class GrantMinutePackFromCheckoutUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private WorkspaceRepositoryInterface&MockObject $workspaceRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private TransactionManagerInterface $transactionManager;
    private GrantMinutePackFromCheckoutUseCase $useCase;

    protected function setUp(): void
    {
        $this->workspaceRepository = $this->createMock(WorkspaceRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->transactionManager = $this->createStub(TransactionManagerInterface::class);
        $this->transactionManager->method('transactional')
            ->willReturnCallback(static fn (callable $cb) => $cb());

        $this->useCase = new GrantMinutePackFromCheckoutUseCase(
            $this->workspaceRepository,
            $this->transactionManager,
            $this->eventDispatcher,
            $this->createStub(LoggerInterface::class),
        );
    }

    private function workspace(int $allocated = 90, int $consumedSeconds = 0): Workspace
    {
        return $this->createEntityState(Workspace::class, [
            'id' => Uuid::v7(),
            'slugId' => 'wrk_pack',
            'name' => 'Cabinet Pack',
            'meetingMinutesAllocated' => $allocated,
            'meetingSecondsConsumed' => $consumedSeconds,
            'subscription' => null,
        ]);
    }

    public function testCreditsMinutesAndDispatchesEvent(): void
    {
        $workspace = $this->workspace(allocated: 90, consumedSeconds: 20 * 60);
        $this->workspaceRepository->method('getById')->willReturn($workspace);
        $this->workspaceRepository->expects($this->once())->method('save')->with($workspace);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (MinutePackPurchasedEvent $e): bool => 'wrk_pack' === $e->workspaceSlugId
                && 300 === $e->minutesGranted
                && 370 === $e->remainingMeetingMinutes
                && 'buyer@cabinet.fr' === $e->recipientEmail
                && 'https://invoice' === $e->invoiceUrl))
            ->willReturnArgument(0);

        ($this->useCase)((string) $workspace->id, 300, 'buyer@cabinet.fr', 'https://invoice');

        self::assertSame(390, $workspace->meetingMinutesAllocated);
    }

    public function testIgnoresNonPositiveMinutes(): void
    {
        $this->workspaceRepository->expects($this->never())->method('getById');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        ($this->useCase)(Uuid::v7()->toRfc4122(), 0, 'buyer@cabinet.fr', null);
    }
}
