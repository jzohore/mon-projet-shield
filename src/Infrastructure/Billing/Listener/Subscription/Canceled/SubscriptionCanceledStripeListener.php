<?php

namespace App\Infrastructure\Billing\Listener\Subscription\Canceled;

use App\Domain\Billing\Event\SubscriptionCanceledEvent;
use App\Infrastructure\Service\Payment\Stripe\StripeService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener(event: SubscriptionCanceledEvent::class)]
readonly class SubscriptionCanceledStripeListener
{
    public function __construct(
        private StripeService $stripeService,
    ) {}

    public function __invoke(SubscriptionCanceledEvent $event): void
    {
        Assert::notNull($event->subscription->stripeSubscriptionId);
        Assert::notNull($event->reason);
        $this->stripeService->cancelSubscription($event->subscription->stripeSubscriptionId, $event->reason);
    }
}
