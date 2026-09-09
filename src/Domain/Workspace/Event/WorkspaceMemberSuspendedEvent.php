<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Event;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;

readonly class WorkspaceMemberSuspendedEvent
{
    public function __construct(
        public User $targetUser,
        public Workspace $workspace,
        public User $actor,
    ) {
    }
}
