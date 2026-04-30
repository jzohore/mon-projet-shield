<?php

namespace App\Domain\Billing\Event;

use App\Domain\Billing\Entity\Subscription;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Contracts\EventDispatcher\Event;

class SubscriptionActivatedEvent extends Event
{
    public function __construct(
        public Workspace $workspace,
        public string $recipientEmail,
        public Subscription $subscription
    ) {}
}
