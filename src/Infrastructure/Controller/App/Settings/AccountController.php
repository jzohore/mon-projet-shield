<?php

namespace App\Infrastructure\Controller\App\Settings;

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
#[Route(path: '/app/settings/account', name: 'app_settings_account')]
class AccountController
{
    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(
        Environment $twig,
        #[CurrentUser]
        User $user,
    ): Response {
        return new Response(
            $twig->render('@app/settings/account.html.twig', [
                'page_title' => 'Paramètres - Mon compte',
                'sub_title' => 'Gérez les paramètres et les préférences de votre compte.',
            ])
        );
    }
}
