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
#[Route(path: '/app/onboarding/workspace', name: 'app_onboarding_workspace', methods: ['GET', 'POST'])]
final class OnBoardingWorkspaceController
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
            $twig->render('@app/onboarding/workspace.html.twig', [
                'page_title' => 'Trouvons votre entreprise',
                'sub_title' => 'Saisissez le nom de votre société ou votre numéro SIREN. 
                Nous récupérerons automatiquement vos informations légales pour configurer votre espace.',
                'user_slug_id' => $user->slugId,
            ])
        );
    }
}
