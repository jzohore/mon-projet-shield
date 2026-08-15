<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;

readonly class UserUpdateProfilEvent
{
    public function __construct(
        public User $user,
        public Workspace $workspace,
    ) {
    }
}
