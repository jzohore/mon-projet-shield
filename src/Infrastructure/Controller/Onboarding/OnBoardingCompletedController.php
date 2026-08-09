<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Onboarding;

use App\Application\User\UseCase\UpdateOnboardingStatusUseCase;
use App\Domain\User\Enum\OnboardingStatus;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[AsController]
#[Route(path: '/app/onboarding/completed', name: 'app_onboarding_completed', methods: ['POST'])]
#[IsCsrfTokenValid('onboarding_completed')]
readonly class OnBoardingCompletedController
{
    public function __construct(
        private UpdateOnboardingStatusUseCase $updateOnboardingStatusUseCase,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(): RedirectResponse
    {
        ($this->updateOnboardingStatusUseCase)(OnboardingStatus::COMPLETED);

        return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
    }
}
