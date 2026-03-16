<?php

declare(strict_types=1);

namespace App\Infrastructure\Employees\Controller;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/app/employees/list', name: 'app_employees_list', methods: ['GET', 'POST'])]
final class EmployeesListController
{
    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(
        Environment $twig,
        #[CurrentUser]
        ?User $user,
        WorkspaceRepositoryInterface $workspaceRepository
    ): Response {
        return new Response(
            $twig->render('@app/employees/list.html.twig', [
                'page_title' => 'Équipe & Accès',
            ])
        );
    }
}
