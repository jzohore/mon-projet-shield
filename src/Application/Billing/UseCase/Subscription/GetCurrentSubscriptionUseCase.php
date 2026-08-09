<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCase\Subscription;

use App\Application\Billing\DTO\Response\SubscriptionInfoResponse;
use App\Domain\Billing\Service\WorkspaceQuotaManager;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Infrastructure\Service\Payment\Stripe\StripeService;
use Webmozart\Assert\Assert;

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

        // 1. Tell PHPStan (and the app) that we absolutely require a subscription here
        Assert::notNull($subscription);
        Assert::notNull($subscription->stripeSubscriptionId);

        $searchesUsedThisMonth = null;

        // FIX: Removed the redundant 'if ($subscription !== null)'
        // because the Assertion above already guaranteed it's not null.
        if ($subscription->isValid()) {
            $searchesUsedThisMonth = $this->quotaManager->getSearchesCountThisMonth($workspace);
        }

        $retrieveSubInStripe = $this->stripeService->getSubscription($subscription->stripeSubscriptionId);

        /** * FIX: Accessing the price/plan safely.
         * In the Stripe SDK, the plan is typically found on the first item of the subscription.
         * We use null-coalescing to avoid "undefined property" crashes.
         */
        $firstItem = $retrieveSubInStripe->items->data[0] ?? null;
        $basePriceCents = $firstItem?->plan->amount ?? 0;

        $basePriceEuros = $basePriceCents / 100;

        $invoices = $this->stripeService->getInvoicesBySub($subscription->stripeSubscriptionId);

        return SubscriptionInfoResponse::fromEntity(
            $subscription,
            $searchesUsedThisMonth,
            $basePriceEuros,
            $invoices
        );
    }
}
