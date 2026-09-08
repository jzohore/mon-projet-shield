<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCase\Subscription;

use App\Domain\Billing\Entity\Subscription;
use App\Domain\Billing\Enum\Plan;
use App\Domain\Billing\Repository\SubscriptionRepositoryInterface;
use App\Domain\Workspace\Enum\WorkspaceType;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Domain\Workspace\Service\SeatAvailability;
use App\Infrastructure\Service\Payment\Stripe\StripeService;

/**
 * Ajuste le nombre de sièges d'un abonnement cabinet : met à jour la quantité
 * facturée sur Stripe (prorata immédiat) puis, optimiste, le compteur local
 * (le webhook `customer.subscription.updated` confirmera).
 */
readonly class UpdateSubscriptionSeatsUseCase
{
    public function __construct(
        private CurrentWorkspaceProvider $currentWorkspaceProvider,
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private SeatAvailability $seatAvailability,
        private StripeService $stripeService,
    ) {
    }

    public function __invoke(int $desiredSeats): void
    {
        $workspace = $this->currentWorkspaceProvider->getWorkspace();
        $subscription = $workspace->subscription;

        if (!$subscription instanceof Subscription || !$subscription->isValid() || null === $subscription->stripeSubscriptionId) {
            throw new \DomainException('Aucun abonnement actif : impossible d\'ajuster les sièges.');
        }

        $minPlanSeats = Plan::forWorkspaceType($workspace->isFirm() ? WorkspaceType::FIRM : WorkspaceType::INDIVIDUAL)->getMinSeats();
        $floor = max($minPlanSeats, $this->seatAvailability->usedSeats($workspace));

        if ($desiredSeats < $floor) {
            throw new \DomainException(sprintf('Vous ne pouvez pas descendre en dessous de %d sièges (minimum de l\'offre ou sièges déjà occupés).', $floor));
        }

        if ($desiredSeats === $subscription->seatsCount) {
            return;
        }

        $this->stripeService->updateSubscriptionSeats($subscription->stripeSubscriptionId, $desiredSeats);

        $subscription->updateSeats($desiredSeats);
        $this->subscriptionRepository->save($subscription);
    }
}
