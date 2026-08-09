<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Controller;

use App\Application\Workspace\UseCase\Invitation\AcceptInvitationUseCase;
use App\Domain\Workspace\Exception\InvitationNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[AsController]
#[Route(path: '/portal/invitation/accept', name: 'portal_user_invitation_accept', methods: ['POST'])]
#[IsCsrfTokenValid('accept-invitation')]
class AcceptWorkspaceInvitationController extends AbstractController
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly AcceptInvitationUseCase $acceptInvitationUseCase,
        private readonly Security $security,
    ) {
    }

    public function __invoke(): Response
    {
        $id = $this->requestStack->getSession()->get('wrk_inv_id');

        if (!$id) {
            $this->addFlash('error', 'Le lien d\'invitation est invalide ou expiré.');

            return $this->redirectToRoute('portal_user_invitation');
        }

        try {
            // 1. Le métier fait son travail
            $user = ($this->acceptInvitationUseCase)($id);

            // 2. L'infrastructure connecte l'utilisateur
            $this->security->login(
                $user,
                'security.authenticator.form_login.main',
                'main'
            );

            $this->addFlash('success', 'Bienvenue dans votre nouvel espace !');

            return $this->redirectToRoute('app_dashboard');
        } catch (InvitationNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_login');
        }
    }
}
