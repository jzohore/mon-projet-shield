<?php

declare(strict_types=1);

namespace App\Application\Billing\DTO\Response;

use App\Domain\Billing\Entity\PricingPlan;

/**
 * Données de la page de tarification : offres persistées + contexte du workspace
 * courant (pour recommander la bonne offre et le bon nombre de sièges).
 */
final readonly class PricingView
{
    /**
     * @param PricingPlan[] $subscriptionPlans
     * @param PricingPlan[] $minutePacks
     */
    public function __construct(
        public array $subscriptionPlans,
        public array $minutePacks,
        public bool $isFirm,
        public bool $hasActiveSubscription,
        public string $recommendedPlanKey,
        public int $suggestedSeats,
        public int $trialDossiersRemaining,
        public int $remainingMeetingMinutes,
        public int $meetingMinutesAllocated,
    ) {
    }
}
