<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Contracts\EventDispatcher\Event;

final class UserOnboardingCompletedEvent extends Event
{
    public function __construct(
        public readonly User $user,
        public readonly Workspace $workspace,
    ) {
    }
}
