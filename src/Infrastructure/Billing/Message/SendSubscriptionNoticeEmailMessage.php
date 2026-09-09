<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Message;

/**
 * E-mail transactionnel générique pour un changement d'état d'abonnement
 * (suspension, reprise, offre de fidélité appliquée).
 */
final readonly class SendSubscriptionNoticeEmailMessage
{
    public function __construct(
        public string $recipientEmail,
        public string $workspaceName,
        public string $subject,
        public string $headline,
        public string $body,
    ) {
    }
}
