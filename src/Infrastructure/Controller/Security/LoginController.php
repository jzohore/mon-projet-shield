<?php

namespace App\Infrastructure\Controller\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/login', name: 'app_login', methods: ['GET', 'POST'])]
class LoginController
{
    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(
        Environment $twig,
        UserInterface $user,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        if ($user) {
            return new RedirectResponse($urlGenerator->generate('app_dashboard'));
        }
        return new Response(
            $twig->render('@security/login.html.twig', [
                'page_title' => 'Votre tableau de bord',
            ])
        );
    }
}
