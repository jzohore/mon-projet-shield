<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Event;

use App\Domain\Workspace\Entity\Workspace;

readonly class WorkspaceSuspendedEvent
{
    public function __construct(
        public Workspace $workspace,
    ) {
    }
}
