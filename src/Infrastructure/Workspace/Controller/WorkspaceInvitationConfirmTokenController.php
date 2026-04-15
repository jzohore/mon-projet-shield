<?php

namespace App\Infrastructure\Workspace\Controller;

use App\Application\Workspace\UseCase\GetInvitationByTokenUseCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsController]
#[Route(path: '/portal/invitation/confirm/{token}', name: 'portal_user_confirm_token', methods: ['GET'])]
final readonly class WorkspaceInvitationConfirmTokenController
{
    public function __construct(
        private GetInvitationByTokenUseCase $getInvitationByTokenUseCase,
        private UrlGeneratorInterface $urlGenerator,
        private RequestStack $requestStack,
    ) {}

    public function __invoke(string $token): RedirectResponse
    {
        try {
            $invitation = ($this->getInvitationByTokenUseCase)($token);
        } catch (\DomainException $e) {
            return new RedirectResponse($this->urlGenerator->generate('app_login'));
        }

        $this->requestStack->getSession()->set('wrk_inv_id', $invitation->slugId);

        $response = new RedirectResponse($this->urlGenerator->generate('portal_user_invitation'));
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->add([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);

        return $response;
    }
}
