<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Twig\Components\Invitation;

use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Infrastructure\Shared\Component\LiveFlashTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Liste, en lecture seule, des invitations encore en attente du cabinet courant.
 * Rendue à la fois à côté du formulaire d'invitation et dans l'écran « sièges
 * épuisés » : dans ce dernier cas, c'est le seul moyen pour l'administrateur de
 * libérer un siège en annulant une invitation.
 */
#[AsLiveComponent(
    name: 'WorkspacePendingInvitationsComponent',
    template: 'components/Workspace/WorkspacePendingInvitationsComponent.html.twig',
)]
class WorkspacePendingInvitationsComponent
{
    use DefaultActionTrait;
    use LiveFlashTrait;

    public function __construct(
        private readonly CurrentWorkspaceProvider $currentWorkspaceProvider,
        private readonly WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
    ) {
    }

    /**
     * @return array<int, WorkspaceInvitation>
     */
    public function getInvitations(): array
    {
        return $this->workspaceInvitationRepository->findPendingByWorkspace(
            $this->currentWorkspaceProvider->getWorkspace(),
        );
    }

    /**
     * Une invitation vient d'être annulée dans un composant enfant. On porte le
     * message de succès ici : c'est ce composant qui survit au re-rendu de la liste.
     */
    #[LiveListener('revoke_invitation')]
    public function onInvitationRevoked(#[LiveArg] string $email = ''): void
    {
        $this->addLiveFlash(
            'success',
            '' !== $email ? sprintf('Invitation de %s annulée.', $email) : 'Invitation annulée.',
        );
    }
}
