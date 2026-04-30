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
#[Route(path: '/app/onboarding/workspace/manual', name: 'app_onboarding_workspace_manual_config', methods: ['GET', 'POST'])]
final class OnBoardingWorkspaceConfigurationController
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
            $twig->render('@app/onboarding/workspace_manual.html.twig', [
                'page_title' => 'Configuration manuelle',
                'sub_title' => 'Renseignez les informations de votre structure. Si vous êtes en cours 
                d\'immatriculation, vous pourrez ajouter votre SIRET plus tard.',
                'user_slug_id' => $user->slugId,
            ])
        );
    }
}
