<?php

namespace App\Domain\Subscription\Enum;

enum Plan: string
{
    case TRIAL = 'trial';
    case SOLO = 'solo';
    case PRO = 'pro';

    /**
     * Règle métier : Combien de sièges (membres) sont inclus par défaut dans ce plan ?
     */
    public function getIncludedSeats(): int
    {
        return match ($this) {
            self::TRIAL => 10, // Généreux pour tester la collaboration pendant 14 jours
            self::SOLO  => 1,  // L'indépendant travaille seul
            self::PRO   => 5,  // Le cabinet a 5 places incluses (facturation supp. au-delà)
        };
    }

    /**
     * Label propre pour l'affichage (ex: factures, dashboard administrateur)
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::TRIAL => 'Essai gratuit (14 jours)',
            self::SOLO  => 'Plan Indépendant',
            self::PRO   => 'Plan Cabinet (Pro)',
        };
    }
}
