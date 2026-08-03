<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Enum;

enum TransactionType: string
{
    // 🔴 DÉBITS (Consommation de services)
    case KYC_ANALYSIS = 'kyc_analysis';
    case ADVISORY_REPORT = 'advisory_report';

    // 🟢 CRÉDITS (Ajout de jetons)
    case STRIPE_PURCHASE = 'stripe_purchase';
    case RETENTION_BONUS = 'retention_bonus';
    case REFUND = 'refund'; // Pour les annulations de dossiers DRAFT dont on a parlé
    case MANUAL_ADJUSTMENT = 'manual_adjustment'; // Indispensable pour ton back-office (Support)

    case SCREENING_SEARCH = 'screening_search';

    /**
     * Retourne le libellé propre pour l'affichage humain (Dashboard).
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::KYC_ANALYSIS => 'Analyse dossier KYC',
            self::ADVISORY_REPORT => 'Rapport d\'adéquation MIF 2',
            self::STRIPE_PURCHASE => 'Rechargement de crédits',
            self::RETENTION_BONUS => 'Crédits offerts',
            self::REFUND => 'Remboursement (Annulation)',
            self::MANUAL_ADJUSTMENT => 'Ajustement manuel (Support client)',
            self::SCREENING_SEARCH => 'Recherche LCB-FT',
        };
    }

    /**
     * Permet de vérifier s'il s'agit d'une entrée ou d'une sortie.
     * Très pratique pour valider que le montant ($amount) a le bon signe,
     * ou pour l'UI (ex: if transaction.type.isCredit ? 'text-green-600' : 'text-slate-900').
     */
    public function isCredit(): bool
    {
        return match ($this) {
            self::STRIPE_PURCHASE,
            self::RETENTION_BONUS,
            self::REFUND,
            self::MANUAL_ADJUSTMENT => true,

            self::KYC_ANALYSIS,
            self::ADVISORY_REPORT => false,
            self::SCREENING_SEARCH => throw new \Exception('To be implemented'),
        };
    }
}
