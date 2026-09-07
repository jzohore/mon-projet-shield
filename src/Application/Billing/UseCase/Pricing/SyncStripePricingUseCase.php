<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCase\Pricing;

use App\Domain\Billing\Entity\PricingPlan;
use App\Domain\Billing\Enum\MinutePack;
use App\Domain\Billing\Enum\Plan;
use App\Domain\Billing\Enum\PricingPlanKind;
use App\Domain\Billing\Repository\PricingPlanRepositoryInterface;
use App\Infrastructure\Service\Payment\Stripe\StripeService;

/**
 * Provisionne les offres KYSURE (Phase 1) : crée le produit + prix Stripe quand
 * il manque et persiste les identifiants en base (entité PricingPlan).
 * Idempotent : re-lançable après un reset de base sans créer de doublon en base
 * (un nouveau prix Stripe est créé si l'offre n'était pas encore provisionnée).
 */
readonly class SyncStripePricingUseCase
{
    public function __construct(
        private PricingPlanRepositoryInterface $pricingPlanRepository,
        private StripeService $stripeService,
    ) {
    }

    /**
     * @return list<array{key: string, action: 'created'|'updated', priceId: string|null}>
     */
    public function __invoke(): array
    {
        $report = [];

        foreach ($this->offerings() as $offering) {
            $plan = $this->pricingPlanRepository->findByKey($offering['key']);

            if (!$plan instanceof PricingPlan) {
                $plan = PricingPlan::create(
                    planKey: $offering['key'],
                    kind: $offering['kind'],
                    label: $offering['label'],
                    unitAmountCents: $offering['amount'],
                    meetingMinutes: $offering['minutes'],
                    minSeats: $offering['minSeats'],
                );
            } else {
                $plan->updatePricing($offering['amount'], $offering['minutes'], $offering['minSeats'], $offering['label']);
            }

            if (!$plan->isProvisioned()) {
                $stripe = $this->stripeService->createPricingPlan(
                    name: $offering['label'],
                    description: $offering['description'],
                    unitAmountCents: $offering['amount'],
                    recurring: $offering['kind']->isRecurring(),
                    metadata: ['plan_key' => $offering['key']],
                );
                $plan->linkStripe($stripe['product_id'], $stripe['price_id']);
                $action = 'created';
            } else {
                $action = 'updated';
            }

            $this->pricingPlanRepository->save($plan);
            $report[] = ['key' => $offering['key'], 'action' => $action, 'priceId' => $plan->stripePriceId];
        }

        return $report;
    }

    /**
     * @return list<array{key: string, kind: PricingPlanKind, label: string, description: string, amount: int, minutes: int, minSeats: int}>
     */
    private function offerings(): array
    {
        $rows = [];

        foreach (Plan::cases() as $plan) {
            $rows[] = [
                'key' => $plan->getPricingKey(),
                'kind' => PricingPlanKind::SEAT_SUBSCRIPTION,
                'label' => sprintf('KYSURE %s — par siège', $plan->getLabel()),
                'description' => 'Abonnement mensuel KYSURE facturé au siège. Dossiers de conformité illimités, minutes d\'entretien incluses.',
                'amount' => $plan->getMonthlyPriceCentsPerSeat(),
                'minutes' => $plan->getIncludedMinutesPerSeat(),
                'minSeats' => $plan->getMinSeats(),
            ];
        }

        foreach (MinutePack::cases() as $pack) {
            $rows[] = [
                'key' => $pack->getPricingKey(),
                'kind' => PricingPlanKind::MINUTE_PACK,
                'label' => sprintf('KYSURE — pack %s', $pack->getLabel()),
                'description' => sprintf('Pack de %d minutes d\'entretien prépayées, sans expiration.', $pack->getMinutes()),
                'amount' => $pack->getPriceCents(),
                'minutes' => $pack->getMinutes(),
                'minSeats' => 1,
            ];
        }

        return $rows;
    }
}
