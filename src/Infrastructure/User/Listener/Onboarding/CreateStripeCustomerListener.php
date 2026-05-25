<?php

namespace App\Infrastructure\User\Listener\Onboarding;

use App\Application\User\UseCase\UpdateProfilUseCase;
use App\Domain\User\Event\UserOnboardingCompletedEvent;
use App\Infrastructure\Service\Payment\Stripe\StripeService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: UserOnboardingCompletedEvent::class)]
readonly class CreateStripeCustomerListener
{
    public function __construct(
        private StripeService $stripeService,
        private UpdateProfilUseCase $updateProfilUseCase,
    ) {}

    public function __invoke(UserOnboardingCompletedEvent $event): void
    {
        $user = $event->user;
        $this->stripeService->createStripeCustomer($user);
        ($this->updateProfilUseCase)($user);
    }
}
