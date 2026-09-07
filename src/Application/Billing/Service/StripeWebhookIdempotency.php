<?php

declare(strict_types=1);

namespace App\Application\Billing\Service;

use App\Domain\Billing\Repository\ProcessedStripeEventRepositoryInterface;

/**
 * Garde d'idempotence des webhooks Stripe, exposé à la couche contrôleur
 * (qui n'a pas le droit de dépendre directement d'un repository de domaine).
 */
readonly class StripeWebhookIdempotency
{
    public function __construct(
        private ProcessedStripeEventRepositoryInterface $processedEvents,
    ) {
    }

    public function isAlreadyProcessed(string $eventId): bool
    {
        return $this->processedEvents->isProcessed($eventId);
    }

    public function markProcessed(string $eventId): void
    {
        $this->processedEvents->markProcessed($eventId);
    }
}
