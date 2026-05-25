<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Event;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use Symfony\Contracts\EventDispatcher\Event;

class WorkspaceInvitationCreatedEvent extends Event
{
    public function __construct(
        public readonly WorkspaceInvitation $workspaceInvitation,
        public readonly Workspace $workspace,
        public readonly User $user,
    ) {}
}
