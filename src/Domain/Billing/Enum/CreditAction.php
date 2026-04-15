<?php

namespace App\Domain\Billing\Enum;

enum CreditAction: string
{
    // On liste toutes les actions facturables du SaaS
    case SCREENING_SEARCH = 'screening_search';
    case MONITORING_1_MONTH = 'monitoring_1_month';
    case MONITORING_3_MONTHS = 'monitoring_3_months';
    case MONITORING_6_MONTHS = 'monitoring_6_months';
    case PDF_GENERATION = 'pdf_generation'; // Si tu veux le facturer un jour

    /**
     * C'est LA grille tarifaire centrale de l'application
     */
    public function getCost(): int
    {
        return match ($this) {
            self::SCREENING_SEARCH => 1,       // 1 recherche = 1 crédit
            self::MONITORING_1_MONTH => 5,     // 5 crédits
            self::MONITORING_3_MONTHS => 12,   // 12 crédits (avantage tarifaire)
            self::MONITORING_6_MONTHS => 20,   // 20 crédits
            self::PDF_GENERATION => 0,         // Actuellement gratuit
        };
    }

    /**
     * Pratique pour afficher sur la facture ou l'historique du client
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::SCREENING_SEARCH => 'Recherche LCB-FT (One-off)',
            self::MONITORING_1_MONTH => 'Surveillance continue (1 mois)',
            self::MONITORING_3_MONTHS => 'Surveillance continue (3 mois)',
            self::MONITORING_6_MONTHS => 'Surveillance continue (6 mois)',
            self::PDF_GENERATION => 'Génération de certificat PDF',
        };
    }
}
