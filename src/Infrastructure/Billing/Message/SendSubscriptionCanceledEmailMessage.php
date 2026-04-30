<?php

namespace App\Infrastructure\Billing\Message;

readonly class SendSubscriptionCanceledEmailMessage
{
    public function __construct(
        public string $recipientEmail,
        public string $workspaceName,
        public string $endDate,
    ) {}
}
