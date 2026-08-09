<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Controller;

use App\Application\Workspace\UseCase\Invitation\GetCurrentInvitationUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/portal/invitation', name: 'portal_user_invitation', methods: ['GET'])]
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
        $id = $this->requestStack->getSession()->get('wrk_inv_id');

        if (!$id) {
            $this->addFlash('error', 'Le lien d\'invitation est invalide ou expiré.');

            return $this->redirectToRoute('portal_user_invitation');
        }

        $invitation = ($this->getCurrentInvitationUseCase)($id);

        return $this->render('@app/workspace/invitation/accept_invitation.html.twig', [
            'page_title' => 'Rejoindre votre équipe',
            'invitation' => $invitation,
        ]);
    }
}
