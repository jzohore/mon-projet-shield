<?php

namespace App\Infrastructure\Workspace\Controller;

use App\Application\Workspace\UseCase\GetCurrentInvitationUseCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Webmozart\Assert\Assert;

#[AsController]
#[Route(path: '/portal/invitation', name: 'portal_user_invitation', methods: ['GET'])]
final readonly class PortalWorkspaceInvitationController
{
    public function __construct(
        private RequestStack $requestStack,
        private GetCurrentInvitationUseCase $getCurrentInvitationUseCase,
        private UrlGeneratorInterface $urlGenerator,
        private Environment $twig,
    ) {}

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(): Response
    {
        $id = $this->requestStack->getSession()->get('wrk_inv_id');
        Assert::notNull($id);
        if (! $id) {
            return new Response($this->urlGenerator->generate('app_login'));
        }

        $invitation = ($this->getCurrentInvitationUseCase)($id);
        return new Response(
            $this->twig->render('app/workspace/invitation/accept_invitation.html.twig', [
                'page_title' => 'Rejoindre votre équipe',
                'invitation' => $invitation,
            ])
        );
    }
}
