<?php

namespace App\Domain\Billing\Repository;

use App\Domain\Billing\Entity\Subscription;

interface SubscriptionRepositoryInterface
{
    public function getByStripeId(string $stripeSubscriptionId): Subscription;

    public function save(Subscription $subscription): void;
}
