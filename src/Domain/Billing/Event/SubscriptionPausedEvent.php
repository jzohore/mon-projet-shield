<?php

declare(strict_types=1);

namespace App\Domain\Billing\Event;

use App\Domain\Billing\Entity\Subscription;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;

final readonly class SubscriptionPausedEvent
{
    public function __construct(
        public Subscription $subscription,
        public User $user,
        public Workspace $workspace,
    ) {
    }
}
