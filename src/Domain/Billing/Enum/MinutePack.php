<?php

declare(strict_types=1);

namespace App\Domain\Billing\Enum;

/**
 * Packs de minutes d'entretien prépayés, achetés à l'unité (prix one-time
 * Stripe). Créditent le solde de minutes du workspace, sans expiration.
 * Pas de dépassement auto-facturé : solde à 0 → l'enregistrement est bloqué.
 */
enum MinutePack: string
{
    case SMALL = 'minutes_300';
    case MEDIUM = 'minutes_600';
    case LARGE = 'minutes_1500';

    public function getMinutes(): int
    {
        return match ($this) {
            self::SMALL => 300,
            self::MEDIUM => 600,
            self::LARGE => 1500,
        };
    }

    /** Prix en centimes (prix de lancement). */
    public function getPriceCents(): int
    {
        return match ($this) {
            self::SMALL => 2900,
            self::MEDIUM => 4900,
            self::LARGE => 9900,
        };
    }

    public function getLabel(): string
    {
        return sprintf('%d minutes', $this->getMinutes());
    }

    public function getStripePriceEnvKey(): string
    {
        return match ($this) {
            self::SMALL => 'STRIPE_PRICE_MINUTES_300',
            self::MEDIUM => 'STRIPE_PRICE_MINUTES_600',
            self::LARGE => 'STRIPE_PRICE_MINUTES_1500',
        };
    }
}
