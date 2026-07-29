<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Message;

readonly class SendSubscriptionActivatedEmailMessage
{
    public function __construct(
        public string $recipientEmail,
        public string $workspaceName,
    ) {
    }
}
