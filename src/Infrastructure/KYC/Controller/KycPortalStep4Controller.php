<?php

namespace App\Infrastructure\KYC\Controller;

use App\Application\Kyc\UseCase\GetCurrentKycFolderUseCase;
use App\Domain\Kyc\Enum\KycFolderStatus;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

#[AsController]
#[Route(path: '/portal/kyc/completed', name: 'portal_kyc_completed', methods: ['GET', 'POST'])]
final readonly class KycPortalStep4Controller
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

        if ($kycFolder->status !== KycFolderStatus::IN_REVIEW && !$kycFolder->isCertified) {
            return new RedirectResponse($urlGenerator->generate('portal_kyc_documents'));
        }

        return new Response(
            $twig->render('app/kyc/portal/step4_completed.html.twig', [
                'page_title' => 'Dossier transmis - ' . $kycFolder->workspaceName,
                'folder' => $kycFolder,
            ])
        );
    }
}
