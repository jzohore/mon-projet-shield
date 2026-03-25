<?php

namespace App\Infrastructure\KYC\Controller;

use App\Application\Kyc\UseCase\GetCurrentKycFolderUseCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

#[AsController]
#[Route(path: '/portal/kyc/company', name: 'portal_kyc_company', methods: ['GET', 'POST'])]
final readonly class KycPortalController
{
    public function __construct(
        private GetCurrentKycFolderUseCase $getCurrentKycFolderUseCase,
    ) {}
    public function __invoke(
        Environment $twig,
        RequestStack $request,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $id = $request->getSession()->get('kyc_slug_id');

        if (! $id) {
            return new Response($urlGenerator->generate('app_login'));
        }

        $kycFolder = ($this->getCurrentKycFolderUseCase)($id);
        return new Response(
            $twig->render('app/kyc/portal/kyc_portal_company.html.twig', [
                'page_title' => 'Identifiez votre société',
                'folder' => $kycFolder,
            ])
        );
    }
}
