<?php

namespace App\Infrastructure\User\Controller;

use App\Application\User\UseCase\Dashboard\DismissOnboardingUseCase;
use App\Domain\User\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Twig\Environment;
use Webmozart\Assert\Assert;

#[AsController]
#[Route(path: '/app/user/dismiss-onboarding', name: 'app_user_dismiss_onboarding', methods: ['POST'])]
final readonly class DismissOnboardingController
{
    /**
     * @param Environment $twig
     * @param User $user
     * @param DismissOnboardingUseCase $dismissOnboardingUseCase
     * @return Response
     */
    public function __invoke(
        Environment $twig,
        #[CurrentUser]
        User $user,
        DismissOnboardingUseCase
        $dismissOnboardingUseCase,
    ): Response {
        $userId = $user->slugId;
        Assert::notNull($userId, 'User id is null');
        ($dismissOnboardingUseCase)($user->slugId);
        return new JsonResponse(['status' => 'success']);
    }
}
