<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\App;

use App\Application\Dashboard\UseCase\GetUserDashboardStatsUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/app/dashboard', name: 'app_dashboard')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly GetUserDashboardStatsUseCase $getUserDashboardStatsUseCase,
    ) {
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(): Response
    {
        return $this->render('@app/dashboard.html.twig', [
            'page_title' => 'Votre tableau de bord',
            'stats_user' => ($this->getUserDashboardStatsUseCase)(),
        ]);
    }
}
