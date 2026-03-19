<?php

declare(strict_types=1);

namespace App\Infrastructure\Employees\Controller;

use App\Application\Workspace\UseCase\GetCurrentWorkspaceInfo;
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
#[Route(path: '/app/employees/list', name: 'app_employees_list', methods: ['GET', 'POST'])]
final readonly class EmployeesListController
{
    public function __construct(
        private GetCurrentWorkspaceInfo $getCurrentWorkspaceInfo
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
        $workspaceInfo = ($this->getCurrentWorkspaceInfo)($user);

        return new Response(
            $twig->render('@app/employees/list.html.twig', [
                'page_title' => 'Équipe & Accès',
                'workspace_id' => $workspaceInfo->slugId,
            ])
        );
    }
}
