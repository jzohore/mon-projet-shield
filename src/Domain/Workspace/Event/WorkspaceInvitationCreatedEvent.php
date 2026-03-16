<?php

namespace App\Domain\Workspace\Event;

use App\Domain\Workspace\Entity\WorkspaceInvitation;
use Symfony\Contracts\EventDispatcher\Event;

final class WorkspaceInvitationCreatedEvent extends Event
{
    public function __construct(
        public readonly WorkspaceInvitation $workspaceInvitation,
    ) {}
}
