<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Controller;

use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Endroid\QrCode\Builder\BuilderInterface;
use Endroid\QrCode\Color\Color;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class WorkspaceQrCodeController extends AbstractController
{
    public function __construct(
        private readonly BuilderInterface $customQrCodeBuilder,
        private readonly CurrentWorkspaceProvider $currentWorkspaceProvider,
        private readonly UrlGeneratorInterface $router,
    ) {
    }

    #[Route('/app/onboarding/qr', name: 'app_workspace_qr_display', methods: ['GET'])]
    public function displayPage(): Response
    {
        // On récupère le workspace (via ton provider habituel ou le user connecté)
        // Exemple si tu as un helper ou via $this->getUser()->getWorkspace()

        return $this->render('@app/workspace/qr_display.html.twig', [
            'page_title' => 'Entrée en relation Express',
            'is_profil_valid' => $this->isProfileValid(),
        ]);
    }

    #[Route('/app/qr-code/image', name: 'app_workspace_public_qr_image', methods: ['GET'])]
    public function generateImage(): Response
    {
        $workspace = $this->currentWorkspaceProvider->getWorkspace();
        $onboardingUrl = $this->router->generate('app_client_onboarding', [
            'publicToken' => $workspace->publicToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        // Utilisation de la méthode statique moderne d'Endroid v5+
        $result = $this->customQrCodeBuilder->build(
            data: $onboardingUrl,
            size: 400,
            margin: 20,
            foregroundColor: new Color(15, 23, 42), // Slate 900
            backgroundColor: new Color(255, 255, 255) // Blanc
        );

        return new Response($result->getString(), Response::HTTP_OK, [
            'Content-Type' => $result->getMimeType(),
        ]);
    }

    public function isProfileValid(): bool
    {
        $profile = $this->currentWorkspaceProvider->getWorkspace()->regulatoryProfile;
        // (Utilise getRegulatoryProfile() si c'est une méthode)

        if (!$profile instanceof \App\Domain\Firm\Entity\RegulatoryProfile) {
            return false;
        }

        // 🛡️ La forteresse réglementaire : TOUT doit être rempli
        return !in_array($profile->oriasNumber, [null, '', '0'], true)
            && !in_array($profile->professionalAssociation, [null, '', '0'], true) // ex: ANACOFI, CNCGP...
            && !in_array($profile->rcProInsurer, [null, '', '0'], true)            // Compagnie d'assurance
            && !in_array($profile->rcProPolicyNumber, [null, '', '0'], true);      // Numéro de police
    }
}
