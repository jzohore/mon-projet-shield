<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Event;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;

readonly class WorkspaceMemberRevokedEvent
{
    public function __construct(
        public User $revokedUser,
        public Workspace $workspace,
        public User $actor,
    ) {
    }
}
