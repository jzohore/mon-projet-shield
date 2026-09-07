<?php

declare(strict_types=1);

namespace App\Domain\Billing\Enum;

enum PricingPlanKind: string
{
    /** Abonnement mensuel facturé au siège (récurrent). */
    case SEAT_SUBSCRIPTION = 'seat_subscription';

    /** Pack de minutes d'entretien prépayées (paiement ponctuel). */
    case MINUTE_PACK = 'minute_pack';

    public function isRecurring(): bool
    {
        return self::SEAT_SUBSCRIPTION === $this;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::SEAT_SUBSCRIPTION => 'Abonnement au siège',
            self::MINUTE_PACK => 'Pack de minutes',
        };
    }
}
