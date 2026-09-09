<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\App\Settings;

use App\Application\Workspace\UseCase\Permissions\UpdateWorkspacePermissionsUseCase;
use App\Domain\Shared\Exception\AbstractDomainException;
use App\Domain\Workspace\Enum\PermissionMode;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Infrastructure\Workspace\Voter\WorkspaceInvitationVoter;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('ROLE_USER')]
#[Route(path: '/app/settings/team-permissions', name: 'app_settings_team_permissions', methods: ['GET', 'POST'])]
final class TeamPermissionsController extends AbstractController
{
    public function __construct(
        private readonly CurrentWorkspaceProvider $currentWorkspaceProvider,
        private readonly UpdateWorkspacePermissionsUseCase $updateWorkspacePermissions,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        // Le statut d'administrateur vit dans workspace_members, pas dans User::roles :
        // on passe donc par le voter (contexte cabinet), jamais par is_granted('ROLE_*').
        $this->denyAccessUnlessGranted(
            WorkspaceInvitationVoter::PERMISSIONS_MANAGE,
            $this->currentWorkspaceProvider->getWorkspace(),
        );

        if ($request->isMethod('POST')) {
            return $this->handleSave($request);
        }

        return $this->renderPage();
    }

    private function handleSave(Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('team-permissions', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide, veuillez réessayer.');

            return $this->redirectToRoute('app_settings_team_permissions');
        }

        try {
            ($this->updateWorkspacePermissions)(
                canInvite: $request->request->getBoolean('collab_can_invite'),
                canManagePortfolio: $request->request->getBoolean('collab_can_manage_portfolio'),
                canEditCabinet: $request->request->getBoolean('collab_can_edit_cabinet'),
                canArchiveFolder: $request->request->getBoolean('collab_can_archive_folder'),
                validationMode: PermissionMode::tryFrom((string) $request->request->get('validation_mode'))
                    ?? PermissionMode::DELEGATED,
            );

            $this->addFlash('success', 'Les droits des collaborateurs ont été mis à jour.');
        } catch (AbstractDomainException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error('Échec de mise à jour des droits collaborateurs', ['error' => $e->getMessage()]);
            $this->addFlash('error', 'Une erreur technique est survenue. Veuillez réessayer plus tard.');
        }

        return $this->redirectToRoute('app_settings_team_permissions');
    }

    private function renderPage(): Response
    {
        return $this->render('@app/settings/team_permissions.html.twig', [
            'page_title' => 'Paramètres - Rôles & permissions',
            'sub_title' => 'Choisissez ce qu\'un collaborateur peut faire sans être administrateur.',
            'workspace' => $this->currentWorkspaceProvider->getWorkspace(),
            'validation_modes' => PermissionMode::cases(),
        ]);
    }
}
