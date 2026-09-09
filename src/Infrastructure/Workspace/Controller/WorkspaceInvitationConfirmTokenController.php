<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Controller;

use App\Application\Workspace\UseCase\Invitation\ValidateInvitationTokenUseCase;
use App\Domain\Shared\Exception\AbstractDomainException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsController]
#[Route(path: '/invitation/confirm/{token}', name: 'portal_user_confirm_token', methods: ['GET'])]
class WorkspaceInvitationConfirmTokenController extends AbstractController
{
    public function __construct(
        private readonly ValidateInvitationTokenUseCase $validateInvitationTokenUseCase,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
        private readonly RateLimiterFactory $workspaceInvitationConfirmLimiter,
    ) {
    }

    public function __invoke(string $token): RedirectResponse
    {
        $request = $this->requestStack->getCurrentRequest();
        $clientIp = $request?->getClientIp() ?? 'unknown';

        // Route publique, non authentifiée : on borne l'énumération / le DoS.
        if (!$this->workspaceInvitationConfirmLimiter->create($clientIp)->consume()->isAccepted()) {
            $this->logger->warning('Rate limit atteint sur la confirmation d\'invitation collaborateur', [
                'ip' => $clientIp,
                'token_hash' => substr(hash('sha256', $token), 0, 12),
            ]);
            $this->addFlash('error', 'Trop de tentatives. Merci de réessayer dans quelques minutes.');

            return new RedirectResponse($this->urlGenerator->generate('app_login'));
        }

        try {
            $invitation = ($this->validateInvitationTokenUseCase)($token);

            $this->requestStack->getSession()->set('wrk_inv_id', $invitation->slugId);

            $response = new RedirectResponse($this->urlGenerator->generate('portal_user_invitation'));
            $response->headers->set('Referrer-Policy', 'no-referrer');
            $response->headers->add([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
            ]);

            return $response;
        } catch (AbstractDomainException $exception) {
            $this->addFlash('error', $exception->getMessage());

            // Jamais le jeton en clair dans les logs : un préfixe de hash suffit à corréler.
            $this->logger->warning('Tentative de récupération d\'invitation introuvable', [
                'token_hash' => substr(hash('sha256', $token), 0, 12),
            ]);
        } catch (\Exception $exception) {
            $this->addFlash('error', 'Le lien d\'invitation est invalide ou expiré.');

            $this->logger->critical('Crash système lors de la validation du token d\'invitation', [
                'error' => $exception->getMessage(),
                'token_hash' => substr(hash('sha256', $token), 0, 12),
            ]);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
