<?php

namespace App\Infrastructure\Compliance\Controller;

use App\Domain\Compliance\Entity\ComplianceFolder;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/app/compliance/new/{type}/{method}/{slugId}', name: 'app_compliance_method_new', methods: ['GET', 'POST'])]
readonly class NewStep2ComplianceController
{
    public function __construct(
        private Environment $twig,
    ) {}

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(
        string $type,
        string $method,
        #[MapEntity(mapping: ['slugId' => 'slugId'])]
        ComplianceFolder $folder,
    ): Response {

        // 💡 Sécurité bonus : Vérifier que le folder appartient bien au Workspace de l'utilisateur
        // (Généralement géré via un Voter Symfony)

        return new Response(
            $this->twig->render('@app/compliance/compliance_new_step_2.html.twig', [
                'page_title' => 'Compléter le dossier',
                'folder'     => $folder,
                'method'     => $method,
                'type'       => $type,
            ])
        );
    }
}
