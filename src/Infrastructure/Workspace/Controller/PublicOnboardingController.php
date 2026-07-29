<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Controller;

use App\Domain\Workspace\Entity\Workspace;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicOnboardingController extends AbstractController
{
    #[Route('/onboarding/{publicToken}', name: 'app_client_onboarding', methods: ['GET', 'POST'])]
    public function __invoke(
        #[MapEntity(mapping: ['publicToken' => 'publicToken'])]
        Workspace $workspace,
    ): Response {
        // C'est ici que tu mettras le formulaire public "Nom, Prénom, Email"
        // pour créer le dossier en direct.

        return $this->render('@app/workspace/onboarding_client.html.twig', [
            'page_title' => 'Entrée en relation Express',
            'workspace' => $workspace,
        ]);
    }
}
