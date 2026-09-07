<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Service;

use App\Domain\Billing\Entity\PricingPlan;
use App\Domain\Billing\Enum\MinutePack;
use App\Domain\Billing\Enum\Plan;
use App\Domain\Billing\Repository\PricingPlanRepositoryInterface;

/**
 * Résout l'identifiant de prix Stripe (`price_...`) d'une offre du domaine, à
 * partir des offres persistées (entité PricingPlan, provisionnées par la
 * commande `app:billing:sync-pricing`).
 */
final readonly class StripePriceResolver
{
    public function __construct(
        private PricingPlanRepositoryInterface $pricingPlanRepository,
    ) {
    }

    public function forPlan(Plan $plan): string
    {
        return $this->priceId($plan->getPricingKey());
    }

    public function forMinutePack(MinutePack $pack): string
    {
        return $this->priceId($pack->getPricingKey());
    }

    public function planFor(Plan $plan): PricingPlan
    {
        return $this->pricingPlanRepository->getByKey($plan->getPricingKey());
    }

    private function priceId(string $planKey): string
    {
        return (string) $this->pricingPlanRepository->getByKey($planKey)->stripePriceId;
    }
}
