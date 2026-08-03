<?php

declare(strict_types=1);

namespace App\Infrastructure\Employees\Controller;

use App\Application\Workspace\UseCase\WorkspaceMember\GetWorkspaceMemberDetailsUseCase;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Exception\MemberNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Webmozart\Assert\Assert;

#[AsController]
#[Route(path: '/app/team/show/{slugId}', name: 'app_employees_show', methods: ['GET'])]
class EmployeesShowController extends AbstractController
{
    public function __construct(
        private readonly GetWorkspaceMemberDetailsUseCase $getMemberDetailsUseCase,
    ) {
    }

    public function __invoke(string $slugId, #[CurrentUser] User $user): Response
    {
        Assert::notNull($user->id, "L'utilisateur connecté doit avoir un ID.");

        try {
            $memberDto = ($this->getMemberDetailsUseCase)($slugId, $user);

            return $this->render('@app/employees/show.html.twig', [
                'page_title' => 'Profil Collaborateur',
                'sub_title' => 'Gérez les accès, les rôles et la sécurité de vos collaborateurs.',
                'member' => $memberDto,
            ]);
        } catch (MemberNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_employees_list');
        }
    }
}
