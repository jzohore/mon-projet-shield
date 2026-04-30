<?php

namespace App\Domain\Billing\Event;

use App\Domain\Billing\Entity\Subscription;
use Symfony\Contracts\EventDispatcher\Event;

class SubscriptionCanceledEvent extends Event
{
    public function __construct(
        public Subscription $subscription,
        public ?string $reason = null
    ) {}
}
