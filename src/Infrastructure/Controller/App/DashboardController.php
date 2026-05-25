<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\App;

use App\Application\Dashboard\UseCase\GetAdminDashboardStatsUseCase;
use App\Application\Dashboard\UseCase\GetUserDashboardStatsUseCase;
use App\Domain\User\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/app/dashboard', name: 'app_dashboard')]
final readonly class DashboardController
{
    public function __construct(
        private GetAdminDashboardStatsUseCase $adminDashboardStatsUseCase,
        private Security $security,
        private GetUserDashboardStatsUseCase $getUserDashboardStatsUseCase,
    ) {}

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
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return new Response(
                $twig->render('@admin/dashboard.html.twig', [
                    'page_title' => 'Centre de Contrôle',
                    'stats' => ($this->adminDashboardStatsUseCase)(),
                ])
            );
        }

        // 🏢 FLUX NORMAL CLIENT (Espace Workspace)
        return new Response(
            $twig->render('@app/dashboard.html.twig', [
                'page_title' => 'Votre tableau de bord',
                'stats_user' => ($this->getUserDashboardStatsUseCase)(),
            ])
        );
    }
}
