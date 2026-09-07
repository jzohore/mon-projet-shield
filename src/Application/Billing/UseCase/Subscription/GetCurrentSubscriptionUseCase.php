<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCase\Subscription;

use App\Application\Billing\DTO\Response\SubscriptionInfoResponse;
use App\Domain\Billing\Entity\Subscription;
use App\Domain\Billing\Service\WorkspaceQuotaManager;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Infrastructure\Service\Payment\Stripe\StripeService;

readonly class GetCurrentSubscriptionUseCase
{
    public function __construct(
        private WorkspaceQuotaManager $quotaManager,
        private CurrentWorkspaceProvider $currentWorkspaceProvider,
        private StripeService $stripeService,
    ) {
    }

    public function __invoke(): SubscriptionInfoResponse
    {
        $workspace = $this->currentWorkspaceProvider->getWorkspace();
        $subscription = $workspace->subscription;

        // Pas d'abonnement (workspace en essai) : on renvoie un état « inactif »
        // sans appeler Stripe.
        if (!$subscription instanceof Subscription) {
            return SubscriptionInfoResponse::fromEntity(null);
        }

        $searchesUsedThisMonth = $subscription->isValid()
            ? $this->quotaManager->getSearchesCountThisMonth($workspace)
            : null;

        // Sans identifiant Stripe (abonnement à peine créé, en attente du
        // webhook), on ne va pas chercher le détail distant.
        if (null === $subscription->stripeSubscriptionId) {
            return SubscriptionInfoResponse::fromEntity($subscription, $searchesUsedThisMonth);
        }

        $remoteSubscription = $this->stripeService->getSubscription($subscription->stripeSubscriptionId);
        $firstItem = $remoteSubscription->items->data[0] ?? null;
        $basePriceEuros = ($firstItem?->plan->amount ?? 0) / 100;

        $invoices = $this->stripeService->getInvoicesBySub($subscription->stripeSubscriptionId);

        return SubscriptionInfoResponse::fromEntity(
            $subscription,
            $searchesUsedThisMonth,
            $basePriceEuros,
            $invoices,
        );
    }
}
