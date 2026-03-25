<?php

namespace App\Infrastructure\KYC\Controller;

use App\Application\Kyc\UseCase\GetKycFolderByTokenUseCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsController]
#[Route(path: '/portal/kyc/confirm/{token}', name: 'portal_kyc_confirm_token', methods: ['GET'])]
final readonly class KycConfirmTokenController
{
    public function __construct(
        private GetKycFolderByTokenUseCase $getKycFolderByTokenUseCase,
    ) {}

    public function __invoke(
        string $token,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator,
    ): RedirectResponse {
        $kycFolder = ($this->getKycFolderByTokenUseCase)($token);
        if (!$kycFolder->isShareTokenValid) {

            return new RedirectResponse($urlGenerator->generate('app_login'));
        }
        $requestStack->getSession()->set('kyc_slug_id', $kycFolder->slugId);
        $response = new RedirectResponse($urlGenerator->generate('portal_kyc_company'));
        $response->headers->set('Referrer-Policy', 'no-referrer');
        // Optionnel: anti-cache
        $response->headers->add([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);

        return $response;
    }
}
