<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Enum;

enum RiskLevel: string
{
    case LOW = 'LOW';               // Risque Faible (Client local, profil simple)
    case MEDIUM = 'MEDIUM';         // Risque Standard
    case HIGH = 'HIGH';             // Risque Élevé (PPE, secteurs à risque, crypto)
    case PROHIBITED = 'PROHIBITED'; // Risque Inacceptable (Sanctions internationales, liste noire)

    /**
     * Règle métier : Combien de temps un dossier est valide avant révision obligatoire ?
     */
    public function getReviewFrequencyInMonths(): int
    {
        return match ($this) {
            self::LOW => 60,         // Révision tous les 5 ans
            self::MEDIUM => 36,      // Révision tous les 3 ans
            self::HIGH => 12,        // Révision tous les ans (Vigilance constante)
            self::PROHIBITED => 0,   // Refus immédiat, pas de validité
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::LOW => 'Faible',
            self::MEDIUM => 'Modéré',
            self::HIGH => 'Élevé',
            self::PROHIBITED => 'Prohibé (Inacceptable)',
        };
    }
}
