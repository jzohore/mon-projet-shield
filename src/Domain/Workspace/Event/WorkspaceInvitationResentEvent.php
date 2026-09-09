<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Event;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Émis quand un administrateur renvoie une invitation (nouveau jeton, nouvel
 * e-mail). Renvoyer prolonge la fenêtre d'accès : l'action doit être tracée.
 */
class WorkspaceInvitationResentEvent extends Event
{
    public function __construct(
        public readonly WorkspaceInvitation $workspaceInvitation,
        public readonly User $resentBy,
        public readonly Workspace $workspace,
    ) {
    }
}
