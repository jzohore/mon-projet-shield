<?php

namespace App\Application\Billing\UseCase\Subscription;

use App\Domain\Billing\Event\SubscriptionCanceledEvent;
use App\Domain\Billing\Repository\SubscriptionRepositoryInterface;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

readonly class CancelPendingSubscriptionUseCase
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private CurrentWorkspaceProvider $currentWorkspaceProvider,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(string $reason): void
    {
        $workspace = $this->currentWorkspaceProvider->getWorkspace();
        $subscription = $workspace->subscription;
        Assert::notNull($subscription);
        $subscription->markAsPendingCancellation($reason);
        $this->subscriptionRepository->save($subscription);
        $this->eventDispatcher->dispatch(new SubscriptionCanceledEvent($subscription, $reason));
    }
}
