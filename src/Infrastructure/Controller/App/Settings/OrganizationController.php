<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\App\Settings;

use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Infrastructure\Workspace\Voter\WorkspaceInvitationVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route(path: '/app/settings/organization', name: 'app_settings_organization')]
class OrganizationController extends AbstractController
{
    public function __construct(
        private readonly CurrentWorkspaceProvider $currentWorkspaceProvider,
    ) {
    }

    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted(
            WorkspaceInvitationVoter::WORKSPACE_EDIT,
            $this->currentWorkspaceProvider->getWorkspace(),
        );

        return $this->render('@app/settings/organization.html.twig', [
            'page_title' => 'Paramètres - Organisation',
            'sub_title' => 'Données légales et structurelles de votre entreprise.',
        ]);
    }
}
