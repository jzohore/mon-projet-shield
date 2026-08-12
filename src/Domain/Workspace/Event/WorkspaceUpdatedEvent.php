<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Event;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;

readonly class WorkspaceUpdatedEvent
{
    public function __construct(
        public Workspace $workspace,
        public string $oldName,
        public string $oldSiren,
        public ?string $email = null,
        public ?string $fullName = null,
        public ?User $user = null,
    ) {
    }
}
