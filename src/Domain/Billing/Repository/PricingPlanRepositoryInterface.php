<?php

declare(strict_types=1);

namespace App\Domain\Billing\Repository;

use App\Domain\Billing\Entity\PricingPlan;
use App\Domain\Billing\Enum\PricingPlanKind;

interface PricingPlanRepositoryInterface
{
    public function save(PricingPlan $plan): void;

    public function findByKey(string $planKey): ?PricingPlan;

    /**
     * @throws \DomainException si l'offre n'existe pas ou n'est pas provisionnée sur Stripe
     */
    public function getByKey(string $planKey): PricingPlan;

    /**
     * @return PricingPlan[]
     */
    public function findByKind(PricingPlanKind $kind): array;

    /**
     * @return PricingPlan[]
     */
    public function findAllActive(): array;
}
