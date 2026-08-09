<?php

declare(strict_types=1);

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
#[Route(path: '/app/settings/logs', name: 'app_settings_logs')]
class LogsController
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
            $twig->render('@app/settings/logs.html.twig', [
                'page_title' => 'Paramètres - Sécurité & Activité',
                'sub_title' => 'Journal immuable des actions effectuées au sein de l\'organisation.',
            ])
        );
    }
}
