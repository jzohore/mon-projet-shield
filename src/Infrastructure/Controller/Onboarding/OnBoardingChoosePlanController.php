<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Onboarding;

use App\Domain\User\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/app/onboarding/plan', name: 'app_onboarding_plan', methods: ['GET', 'POST'])]
final class OnBoardingChoosePlanController
{
    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(
        Environment $twig,
        #[CurrentUser]
        User
        $user,
    ): Response {

        return new Response(
            $twig->render('@app/onboarding/choose_plan.html.twig', [
                'page_title' => 'Comment travaillez-vous ?',
                'page_description' => 'Indiquez-nous la taille de votre structure pour adapter votre espace de travail.',
                'user_slug_id' => $user->slugId,
            ])
        );
    }
}
