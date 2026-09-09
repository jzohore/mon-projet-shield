<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Controller;

use App\Application\Workspace\UseCase\Invitation\GetCurrentInvitationUseCase;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/invitation', name: 'portal_user_invitation', methods: ['GET'])]
class PortalWorkspaceInvitationController extends AbstractController
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly GetCurrentInvitationUseCase $getCurrentInvitationUseCase,
    ) {
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(): Response
    {
        $session = $this->requestStack->getSession();
        $id = $session->get('wrk_inv_id');

        if (!$id) {
            $this->addFlash('error', 'Le lien d\'invitation est invalide ou expiré. Redemandez une invitation à votre administrateur.');

            return $this->redirectToRoute('app_login');
        }

        try {
            $invitation = ($this->getCurrentInvitationUseCase)($id);
        } catch (AbstractDomainException) {
            // Invitation périmée / consommée / révoquée entre-temps : on ne
            // ré-affiche pas une carte morte, on nettoie et on renvoie proprement.
            $session->remove('wrk_inv_id');
            $this->addFlash('error', 'Cette invitation a expiré ou a déjà été utilisée. Demandez une nouvelle invitation à votre administrateur.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('@app/workspace/invitation/accept_invitation.html.twig', [
            'page_title' => 'Rejoindre votre équipe',
            'invitation' => $invitation,
        ]);
    }
}
