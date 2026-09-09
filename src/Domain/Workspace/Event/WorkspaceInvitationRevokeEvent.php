<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Event;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Enum\InvitationRevocationReason;
use Symfony\Contracts\EventDispatcher\Event;

class WorkspaceInvitationRevokeEvent extends Event
{
    public function __construct(
        public readonly WorkspaceInvitation $workspaceInvitation,
        /** L'administrateur qui déclenche l'annulation (l'acteur), pas l'auteur de l'invitation. */
        public readonly User $revokedBy,
        public readonly Workspace $workspace,
        public readonly InvitationRevocationReason $reason,
    ) {
    }
}
