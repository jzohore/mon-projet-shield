<?php

declare(strict_types=1);

namespace App\Domain\Billing\Repository;

interface ProcessedStripeEventRepositoryInterface
{
    public function isProcessed(string $eventId): bool;

    public function markProcessed(string $eventId): void;
}
