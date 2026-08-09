<?php

declare(strict_types=1);

namespace App\Domain\Billing\Event;

use App\Domain\Billing\Entity\Subscription;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Contracts\EventDispatcher\Event;

class SubscriptionActivatedEvent extends Event
{
    public function __construct(
        public Workspace $workspace,
        public string $recipientEmail,
        public Subscription $subscription,
        public User $user,
    ) {
    }
}
