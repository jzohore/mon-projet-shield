<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Event;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Émis quand une invitation est consommée : un tiers obtient un accès réel
 * aux dossiers du cabinet. Événement à effet juridique → journal d'audit.
 */
class WorkspaceInvitationAcceptedEvent extends Event
{
    public function __construct(
        public readonly WorkspaceInvitation $workspaceInvitation,
        public readonly Workspace $workspace,
        public readonly User $newMember,
        /** true : un compte a été créé pour l'occasion ; false : un compte existant a été rattaché. */
        public readonly bool $newAccount,
    ) {
    }
}
