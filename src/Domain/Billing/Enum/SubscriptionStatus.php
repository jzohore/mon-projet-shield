<?php

declare(strict_types=1);

namespace App\Domain\Billing\Enum;

enum SubscriptionStatus: string
{
    case TRIALING = 'trialing';       // En période d'essai
    case ACTIVE = 'active';           // Payé et actif
    case PAST_DUE = 'past_due';       // Échec du dernier paiement (carte bloquée etc)
    case CANCELED = 'canceled';       // Abonnement résilié et terminé
    case INCOMPLETE = 'incomplete';   // Paiement initial en attente d'authentification (3D Secure)

    public function isActive(): bool
    {
        return match ($this) {
            self::ACTIVE, self::TRIALING => true,
            default => false,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Actif',
            self::TRIALING => 'Essai en cours',
            self::PAST_DUE => 'Paiement en retard',
            self::CANCELED => 'Annulé',
            self::INCOMPLETE => 'Paiement incomplet',
        };
    }
}
