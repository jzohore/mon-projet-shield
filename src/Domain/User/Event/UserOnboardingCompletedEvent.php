<?php

namespace App\Domain\User\Event;

use App\Domain\User\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class UserOnboardingCompletedEvent extends Event
{
    public function __construct(
        public readonly User $user
    ) {}
}
