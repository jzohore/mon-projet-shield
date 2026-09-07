<?php

declare(strict_types=1);

namespace App\Tests\Application\Billing\Checkout;

use App\Application\Billing\UseCase\Checkout\RegisterSubscriptionFromCheckoutUseCase;
use App\Domain\Billing\Entity\Subscription;
use App\Domain\Billing\Enum\SubscriptionStatus;
use App\Domain\Billing\Event\SubscriptionActivatedEvent;
use App\Domain\Billing\Repository\SubscriptionRepositoryInterface;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Enum\WorkspaceType;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

final class RegisterSubscriptionFromCheckoutUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private SubscriptionRepositoryInterface&MockObject $subscriptionRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private RegisterSubscriptionFromCheckoutUseCase $useCase;
    private Workspace $workspace;

    protected function setUp(): void
    {
        $this->workspace = $this->createEntityState(Workspace::class, [
            'id' => Uuid::v7(),
            'slugId' => 'wrk_sub',
            'name' => 'Cabinet Sub',
            'type' => WorkspaceType::FIRM,
            'subscription' => null,
        ]);

        $workspaceRepository = $this->createStub(WorkspaceRepositoryInterface::class);
        $workspaceRepository->method('getById')->willReturn($this->workspace);

        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method('getById')->willReturn($this->createEntityState(User::class, ['id' => Uuid::v7()]));

        $this->subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $transactionManager->method('transactional')->willReturnCallback(static fn (callable $cb) => $cb());

        $this->useCase = new RegisterSubscriptionFromCheckoutUseCase(
            $workspaceRepository,
            $this->subscriptionRepository,
            $userRepository,
            $transactionManager,
            $this->eventDispatcher,
            $this->createStub(LoggerInterface::class),
        );
    }

    public function testCreatesActiveSubscriptionAndDispatchesEvent(): void
    {
        $this->subscriptionRepository->method('findByStripeId')->willReturn(null);
        $this->subscriptionRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Subscription $s): bool => 'sub_123' === $s->stripeSubscriptionId
                && SubscriptionStatus::ACTIVE === $s->status
                && 'cabinet' === $s->planReference
                && 3 === $s->seatsCount));

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(SubscriptionActivatedEvent::class))
            ->willReturnArgument(0);

        ($this->useCase)(
            workspaceId: (string) $this->workspace->id,
            userId: Uuid::v7()->toRfc4122(),
            stripeSubscriptionId: 'sub_123',
            stripePriceId: 'price_cab',
            planReference: 'cabinet',
            seats: 3,
            recipientEmail: 'owner@cabinet.fr',
        );
    }

    public function testIsIdempotentWhenSubscriptionAlreadyKnown(): void
    {
        $existing = $this->createEntityState(Subscription::class, ['stripeSubscriptionId' => 'sub_123']);
        $this->subscriptionRepository->method('findByStripeId')->willReturn($existing);
        $this->subscriptionRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        ($this->useCase)(
            workspaceId: (string) $this->workspace->id,
            userId: Uuid::v7()->toRfc4122(),
            stripeSubscriptionId: 'sub_123',
            stripePriceId: 'price_cab',
            planReference: 'cabinet',
            seats: 3,
            recipientEmail: 'owner@cabinet.fr',
        );
    }
}
