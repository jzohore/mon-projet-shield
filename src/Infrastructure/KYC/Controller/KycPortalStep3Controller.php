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
#[Route(path: '/portal/kyc/documents', name: 'portal_kyc_documents', methods: ['GET', 'POST'])]
final readonly class KycPortalStep3Controller
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
            $twig->render('app/kyc/portal/step3_documents.html.twig', [
                'page_title' => 'Dépôt des pièces justificativess',
                'folder' => $kycFolder,
            ])
        );
    }
}
