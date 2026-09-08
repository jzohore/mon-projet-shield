<?php

declare(strict_types=1);

namespace App\Infrastructure\Employees\Controller;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Domain\Workspace\Service\SeatAvailability;
use App\Infrastructure\Workspace\Voter\WorkspaceInvitationVoter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Webmozart\Assert\Assert;

#[AsController]
#[IsGranted('ROLE_USER')]
#[Route(path: '/app/employees/invitation', name: 'app_employees_invitation', methods: ['GET', 'POST'])]
final readonly class EmployeesInvitationController
{
    public function __construct(
        private Environment $twig,
        private CurrentWorkspaceProvider $workspaceProvider,
        private AuthorizationCheckerInterface $authorizationChecker,
        private SeatAvailability $seatAvailability,
    ) {
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(
        #[CurrentUser]
        ?User $user,
    ): Response {
        Assert::notNull($user);

        $workspace = $this->workspaceProvider->getWorkspace();
        $isAdmin = $this->authorizationChecker->isGranted(WorkspaceInvitationVoter::CREATE, $workspace);

        return new Response(
            $this->twig->render('@app/employees/invitation.html.twig', [
                'page_title' => 'Inviter des collaborateurs',
                'user_slug_id' => $user->slugId,
                'is_admin' => $isAdmin,
                'has_free_seat' => $this->seatAvailability->hasFreeSeat($workspace),
                'seats_used' => $this->seatAvailability->usedSeats($workspace),
                'seats_allowed' => $this->seatAvailability->allowedSeats($workspace),
            ])
        );
    }
}
