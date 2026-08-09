<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Controller;

use App\Application\User\UseCase\Dashboard\DismissOnboardingUseCase;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route(path: '/app/user/dismiss-onboarding', name: 'app_user_dismiss_onboarding', methods: ['POST'])]
final readonly class DismissOnboardingController
{
    public function __construct(
        private DismissOnboardingUseCase $dismissOnboardingUseCase,
    ) {
    }

    public function __invoke(): void
    {
        ($this->dismissOnboardingUseCase)();
    }
}
