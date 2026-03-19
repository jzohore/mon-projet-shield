<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\App;

use App\Application\User\UseCase\Dashboard\GetCountMemberUseCase;
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
#[Route(path: '/app/dashboard', name: 'app_dashboard')]
final readonly class DashboardController
{
    public function __construct(private GetCountMemberUseCase $getCountMemberUseCase) {}
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
            $twig->render('@app/dashboard.html.twig', [
                'page_title' => 'Votre tableau de bord',
                'count_members' => ($this->getCountMemberUseCase)($user),
            ])
        );
    }
}
