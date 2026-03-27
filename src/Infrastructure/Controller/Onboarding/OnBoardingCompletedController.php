<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Onboarding;

use App\Application\User\UseCase\UpdateOnboardingStatusUseCase;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OnboardingStatus;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Webmozart\Assert\Assert;

#[AsController]
#[Route(path: '/app/onboarding/completed', name: 'app_onboarding_completed', methods: ['POST'])]
final readonly class OnBoardingCompletedController
{
    public function __construct(
        private UpdateOnboardingStatusUseCase $updateOnboardingStatusUseCase,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function __invoke(
        UrlGeneratorInterface $urlGenerator,
        Request $request,
        #[CurrentUser]
        User
        $user,
    ): RedirectResponse {

        Assert::notNull($user->slugId);
        $token = new CsrfToken('onboarding_completed', $request->request->getString('_csrf_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new AccessDeniedHttpException('Action non autorisée (Token CSRF invalide).');
        }



        ($this->updateOnboardingStatusUseCase)($user->slugId, OnboardingStatus::COMPLETED);
        return new RedirectResponse($urlGenerator->generate('app_dashboard'));
    }
}
