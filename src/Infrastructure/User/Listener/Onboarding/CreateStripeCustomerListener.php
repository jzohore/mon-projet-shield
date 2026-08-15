<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Listener\Onboarding;

use App\Domain\User\Event\UserOnboardingCompletedEvent;
use App\Infrastructure\Service\Payment\Stripe\StripeService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: UserOnboardingCompletedEvent::class)]
readonly class CreateStripeCustomerListener
{
    public function __construct(
        private StripeService $stripeService,
    ) {
    }

    public function __invoke(UserOnboardingCompletedEvent $event): void
    {
        $user = $event->user;
        $this->stripeService->createStripeCustomer($user);
    }
}
