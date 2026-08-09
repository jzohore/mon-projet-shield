<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Event;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Contracts\EventDispatcher\Event;

final class WorkspaceCreatedEvent extends Event
{
    public function __construct(
        public readonly Workspace $workspace,
        public readonly User $user,
    ) {
    }
}
