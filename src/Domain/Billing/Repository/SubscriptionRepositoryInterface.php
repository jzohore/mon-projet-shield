<?php

namespace App\Domain\Billing\Repository;

use App\Domain\Billing\Entity\Subscription;
use App\Domain\Billing\Enum\SubscriptionStatus;

interface SubscriptionRepositoryInterface
{
    public function getByStripeId(string $stripeSubscriptionId): Subscription;

    public function save(Subscription $subscription): void;

    /**
     * Compte le nombre d'abonnements correspondants à une liste de statuts.
     *
     * @param SubscriptionStatus[] $statuses
     */
    public function countByStatuses(array $statuses): int;
}
