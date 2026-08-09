<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCase\Subscription;

use App\Domain\Billing\Event\SubscriptionActivatedEvent;
use App\Domain\Billing\Repository\SubscriptionRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

readonly class ActivateSubscriptionUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private EventDispatcherInterface $eventDispatcher,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(Uuid $workspaceId, string $stripeSubscriptionId, string $recipientEmail, Uuid $userUuid): void
    {
        $workspace = $this->workspaceRepository->getById($workspaceId);
        $user = $this->userRepository->getById($userUuid);

        $subscription = $workspace->subscription;
        Assert::notNull($subscription);

        $subscription->activateSubscription($stripeSubscriptionId);

        $this->subscriptionRepository->save($subscription);

        $this->eventDispatcher->dispatch(new SubscriptionActivatedEvent(
            workspace: $workspace,
            recipientEmail: $recipientEmail,
            subscription: $subscription,
            user: $user,
        ));
    }
}
