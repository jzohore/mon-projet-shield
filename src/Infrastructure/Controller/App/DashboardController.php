<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\App;

use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/app/dashboard', name: 'app_dashboard')]
final class DashboardController
{
    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(
        Environment $twig,
        UserRepositoryInterface $repository,
    ): Response {

        return new Response(
            $twig->render('@app/dashboard.html.twig', [
                'page_title' => 'Votre tableau de bord',
            ])
        );
    }
}
