<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Event;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use Symfony\Contracts\EventDispatcher\Event;

class WorkspaceInvitationRevokeEvent extends Event
{
    public function __construct(
        public readonly WorkspaceInvitation $workspaceInvitation,
        public readonly User $user,
        public readonly Workspace $workspace,
    ) {
    }
}
