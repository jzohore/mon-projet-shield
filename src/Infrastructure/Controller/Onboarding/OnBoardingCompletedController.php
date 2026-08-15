<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Onboarding;

use App\Application\User\UseCase\MarkAsOnboardingCompletedUseCase;
use App\Domain\Workspace\Service\CurrentUserProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[AsController]
#[Route(path: '/app/onboarding/completed', name: 'app_onboarding_completed', methods: ['POST'])]
#[IsCsrfTokenValid('onboarding_completed')]
class OnBoardingCompletedController extends AbstractController
{
    public function __invoke(
        MarkAsOnboardingCompletedUseCase $markAsOnboardingCompletedUseCase,
        CurrentUserProvider $userProvider,
    ): RedirectResponse {
        $user = $userProvider->getUser();
        ($markAsOnboardingCompletedUseCase)(user: $user);

        return $this->redirectToRoute('app_dashboard');
    }
}
