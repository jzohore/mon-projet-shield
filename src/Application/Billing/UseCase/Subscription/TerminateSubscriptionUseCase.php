<?php

namespace App\Application\Billing\UseCase\Subscription;

use App\Domain\Billing\Repository\SubscriptionRepositoryInterface;

readonly class TerminateSubscriptionUseCase
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
    ) {}

    public function __invoke(string $stripeSubscriptionId): void
    {
        // 1. On retrouve l'abonnement dans notre base de données grâce à l'ID Stripe
        $subscription = $this->subscriptionRepository->getByStripeId($stripeSubscriptionId);
        $subscription->markAsTerminate();
        $this->subscriptionRepository->save($subscription);
    }
}
