<?php

namespace App\Domain\User\Event;

use App\Domain\User\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class UserCreatedEvent extends Event
{
    public const string NAME = 'user.created';
    public function __construct(
        public readonly User $user
    ) {}
}
