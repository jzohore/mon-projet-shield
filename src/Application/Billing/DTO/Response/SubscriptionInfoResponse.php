<?php

declare(strict_types=1);

namespace App\Application\Billing\DTO\Response;

use App\Domain\Billing\Entity\Subscription;
use DateTimeImmutable;

/**
 * On définit un alias de type (optionnel mais recommandé pour la lisibilité).
 *
 * @phpstan-type InvoiceArray array{date: DateTimeImmutable, amount: float|int, status: string|null, pdf_url: string|null}
 */
readonly class SubscriptionInfoResponse
{
    /**
     * @param array<int, InvoiceArray> $invoices
     */
    public function __construct(
        public string $status,
        public bool $hasActiveAccess,
        public int $trialDaysRemaining,
        public bool $cancelAtPeriodEnd,
        public array $invoices,
        public ?string $stripeSubscriptionId = null, // Moved to end
        public ?string $currentPeriodEnd = null,     // Added default null
        public ?int $searchesUsedThisMonth = null,   // Added default null
        public ?int $maxSearches = null,             // Added default null
        public ?int $maxUsers = null,                // Added default null
        public ?int $maxMonitoring = null,           // Added default null
        public ?float $basePrice = null,             // Added default null
    ) {
    }

    /**
     * @param array<int, InvoiceArray> $invoices
     */
    public static function fromEntity(
        ?Subscription $subscription,
        ?int $searchesUsedThisMonth = null,
        ?float $basePrice = null,
        array $invoices = [], // Removed nullable type to match constructor
    ): self {
        // FIX: Guard clause for when $subscription is null
        if (!$subscription instanceof Subscription) {
            return new self(
                status: 'inactive',
                hasActiveAccess: false,
                trialDaysRemaining: 0,
                cancelAtPeriodEnd: false,
                invoices: $invoices,
            );
        }

        return new self(
            status: $subscription->status->value,
            hasActiveAccess: $subscription->isValid(),
            trialDaysRemaining: $subscription->getRemainingTrialDays(),
            cancelAtPeriodEnd: $subscription->cancelAtPeriodEnd,
            invoices: $invoices,
            stripeSubscriptionId: $subscription->stripeSubscriptionId,
            // FIX: Using -> instead of ?-> because PHPStan says currentPeriodEnd is non-nullable
            currentPeriodEnd: $subscription->currentPeriodEnd->format('c'),
            searchesUsedThisMonth: $searchesUsedThisMonth,
            maxSearches: Subscription::PLAN_MAX_SEARCHES_PER_MONTH,
            maxUsers: Subscription::PLAN_MAX_USERS,
            maxMonitoring: Subscription::PLAN_MAX_MONITORING,
            basePrice: $basePrice,
        );
    }
}
