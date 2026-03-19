<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Event;

use App\Domain\Workspace\Entity\WorkspaceInvitation;
use Symfony\Contracts\EventDispatcher\Event;

final class WorkspaceInvitationCreatedEvent extends Event
{
    public function __construct(
        public readonly WorkspaceInvitation $workspaceInvitation,
    ) {}
}
