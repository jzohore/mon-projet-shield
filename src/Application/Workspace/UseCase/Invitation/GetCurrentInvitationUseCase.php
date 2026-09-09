<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\Invitation;

use App\Application\Workspace\DTO\Response\WorkspaceInvitationInfoResponse;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Exception\InvitationNotFoundException;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;

readonly class GetCurrentInvitationUseCase
{
    public function __construct(
        private WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
    ) {
    }

    public function __invoke(string $slugId): WorkspaceInvitationInfoResponse
    {
        $invitation = $this->workspaceInvitationRepository->findBySlugId($slugId);

        // On ne ré-affiche jamais la carte d'une invitation périmée / consommée :
        // l'`id` peut rester en session après expiration ou acceptation.
        if (!$invitation instanceof WorkspaceInvitation || !$invitation->isMagicLinkTokenValid()) {
            throw InvitationNotFoundException::withSlugId($slugId);
        }

        return WorkspaceInvitationInfoResponse::fromEntity($invitation);
    }
}
