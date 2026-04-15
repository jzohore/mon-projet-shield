<?php

namespace App\Infrastructure\Workspace\Controller;

use App\Application\Workspace\UseCase\AcceptInvitationUseCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Webmozart\Assert\Assert;

#[AsController]
#[Route(path: '/portal/invitation/accept', name: 'portal_user_invitation_accept', methods: ['POST'])]
#[IsCsrfTokenValid('accept-invitation')]
final readonly class AcceptWorkspaceInvitationController
{
    public function __construct(
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
        private AcceptInvitationUseCase $acceptInvitationUseCase,
    ) {}

    public function __invoke(): Response
    {
        $id = $this->requestStack->getSession()->get('wrk_inv_id');
        Assert::notNull($id);
        if (! $id) {
            return new Response($this->urlGenerator->generate('app_login'));
        }

        ($this->acceptInvitationUseCase)($id);
        return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
    }
}
