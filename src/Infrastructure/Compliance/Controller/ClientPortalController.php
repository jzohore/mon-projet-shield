<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Controller;

use App\Domain\Compliance\Entity\ComplianceDocument;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ClientPortalController extends AbstractController
{
    #[Route('/portal/document/{slugId}', name: 'app_client_portal', requirements: ['slugId' => '[0-9a-fA-F\-]{36}'], methods: ['GET'])]
    public function __invoke(
        #[MapEntity(mapping: ['slugId' => 'slugId'])]
        ComplianceDocument $document,
    ): Response {
        // 2. On récupère l'URL de signature DocuSeal associée au DER
        // En local : "http://localhost:3000/s/xxxx"
        // En prod : "https://docuseal.kysure.fr/s/xxxx"
        $docuSealUrl = $document->docuSealSignatureUrl;

        return $this->render('@app/portal/signature_frame.html.twig', [
            'docusealUrl' => $docuSealUrl,
        ]);
    }
}
