<?php

declare(strict_types=1);

namespace App\Domain\Billing\Event;

/**
 * Un pack de minutes d'entretien prépayées a été payé et crédité au workspace
 * (paiement Stripe ponctuel confirmé par webhook). Déclenche l'e-mail de
 * confirmation + le journal d'audit.
 */
final readonly class MinutePackPurchasedEvent
{
    public function __construct(
        public string $workspaceSlugId,
        public int $minutesGranted,
        public int $remainingMeetingMinutes,
        public string $recipientEmail,
        public string $workspaceName,
        public ?string $invoiceUrl = null,
    ) {
    }
}
