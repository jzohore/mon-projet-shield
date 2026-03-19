<?php

namespace App\Application\Subscription\DTO;

use App\Domain\Subscription\Enum\Plan;

class SubscriptionRequest
{
    public ?string $workspaceSlugId = null;

    public ?Plan $plan = null;

    public ?string $stripeToken = null;

    public ?\DateTimeImmutable $expiresAt = null;
}
