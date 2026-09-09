<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Controller;

use App\Application\Workspace\UseCase\Invitation\AcceptInvitationUseCase;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[AsController]
#[Route(path: '/invitation/accept', name: 'portal_user_invitation_accept', methods: ['POST'])]
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
        $session = $this->requestStack->getSession();
        $id = $session->get('wrk_inv_id');

        if (!$id) {
            $this->addFlash('error', 'Le lien d\'invitation est invalide ou expiré.');

            return $this->redirectToRoute('app_login');
        }

        try {
            // 1. Le métier valide l'invitation et crée / rattache le collaborateur.
            $user = ($this->acceptInvitationUseCase)($id, $this->security->getUser()?->getUserIdentifier());

            // 2. La clé de session a rempli son office : on la retire tout de suite,
            //    avant que login() ne migre la session en la conservant.
            $session->remove('wrk_inv_id');

            // 3. L'infrastructure connecte l'utilisateur sur le firewall applicatif.
            $this->security->login($user, 'security.authenticator.form_login.main', 'main');

            $this->addFlash('success', 'Bienvenue dans votre nouvel espace !');

            return $this->redirectToRoute('app_dashboard');
        } catch (AbstractDomainException $e) {
            $session->remove('wrk_inv_id');
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_login');
        }
    }
}
