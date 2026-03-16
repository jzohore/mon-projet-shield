<?php

declare(strict_types=1);

namespace App\Infrastructure\Employees\Controller;

use App\Domain\User\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Webmozart\Assert\Assert;

#[AsController]
#[Route(path: '/app/employees/invitation', name: 'app_employees_invitation', methods: ['GET', 'POST'])]
final class EmployeesInvitationController
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
    ): Response {
        Assert::notNull($user);
        return new Response(
            $twig->render('@app/employees/invitation.html.twig', [
                'page_title' => 'Inviter des collaborateurs',
                'user_slug_id' => $user->slugId,
            ])
        );
    }
}
