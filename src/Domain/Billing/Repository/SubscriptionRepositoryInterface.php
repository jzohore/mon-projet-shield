<?php

declare(strict_types=1);

namespace App\Domain\Billing\Repository;

use App\Domain\Billing\Entity\Subscription;
use App\Domain\Billing\Enum\SubscriptionStatus;
use Pagerfanta\Pagerfanta;

interface SubscriptionRepositoryInterface
{
    public function getByStripeId(string $stripeSubscriptionId): Subscription;

    public function findByStripeId(string $stripeSubscriptionId): ?Subscription;

    public function save(Subscription $subscription): void;

    /**
     * Compte le nombre d'abonnements correspondants à une liste de statuts.
     *
     * @param SubscriptionStatus[] $statuses
     */
    public function countByStatuses(array $statuses): int;

    /**
     * Liste paginée pour le back-office KYSURE.
     *
     * @return Pagerfanta<Subscription>
     */
    public function getPaginatedSubscriptions(
        int $page,
        int $perPage,
        ?string $search = null,
        ?SubscriptionStatus $status = null,
    ): Pagerfanta;
}
