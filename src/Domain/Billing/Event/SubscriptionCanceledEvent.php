<?php

namespace App\Domain\Billing\Event;

use App\Domain\Billing\Entity\Subscription;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Contracts\EventDispatcher\Event;

class SubscriptionCanceledEvent extends Event
{
    public function __construct(
        public Subscription $subscription,
        public User $user,
        public Workspace $workspace,
        public ?string $reason = null,
    ) {}
}
