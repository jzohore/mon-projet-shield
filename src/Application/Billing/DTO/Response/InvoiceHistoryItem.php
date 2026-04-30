<?php

namespace App\Application\Billing\DTO\Response;

readonly class InvoiceHistoryItem
{
    public function __construct(
        public \DateTimeImmutable $date,
        public float $amount,
        public ?string $status,
        public ?string $pdfUrl
    ) {}
}
