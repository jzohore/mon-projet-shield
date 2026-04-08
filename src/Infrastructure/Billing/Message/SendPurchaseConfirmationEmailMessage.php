<?php

namespace App\Infrastructure\Billing\Message;

readonly class SendPurchaseConfirmationEmailMessage
{
    public function __construct(
        public string $email,
        public string $workspaceName,
        public int $credits,
        public ?string $invoiceUrl
    ) {}
}
