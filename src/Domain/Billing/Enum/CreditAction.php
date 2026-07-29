<?php

declare(strict_types=1);

namespace App\Domain\Billing\Enum;

enum CreditAction: string
{
    // ==========================================
    // ACTIONS FACTURÉES
    // ==========================================
    case SCREENING_SEARCH = 'screening_search';
    case SCREENING_BULK_SEARCH = 'screening_bulk_search';
    case SCREENING_MONITORING_START = 'screening_monitoring_start';
    case SCREENING_MONITORING_RENEWAL = 'screening_monitoring_renewal';
    case SCREENING_ALERT_REVIEW = 'screening_alert_review';

    case KYC_ANALYSIS = 'kyc_analysis';
    case KYC_REVIEW = 'kyc_review';
    case KYC_DOCUMENT_EXTRACTION = 'kyc_document_extraction';
    case KYC_RISK_RECHECK = 'kyc_risk_recheck';

    case PDF_GENERATION = 'pdf_generation';
    case EXPORT_CSV = 'export_csv';
    case AUDIT_LOG_EXPORT = 'audit_log_export';

    // ==========================================
    // ACTIONS SUPPORT / COMPTABLES
    // ==========================================
    case MANUAL_ADJUSTMENT = 'manual_adjustment';
    case REFUND = 'refund';
    case PROMO_CREDIT = 'promo_credit';
    case RETENTION_BONUS = 'retention_bonus';

    // ==========================================
    // ACTIONS GAMIFIÉES / ONBOARDING
    // ==========================================
    case REWARD_ONBOARDING_COMPLETED = 'reward_onboarding_completed';
    case REWARD_COMPLETE_PROFILE = 'reward_complete_profile';
    case REWARD_ENABLE_2FA = 'reward_enable_2fa';
    case REWARD_INVITE_COLLEAGUE = 'reward_invite_colleague';
    case REWARD_ADD_CREDIT_CARD = 'reward_add_credit_card';
    case REWARD_FIRST_SEARCH = 'reward_first_search';
    case REWARD_WORKSPACE_SETUP = 'reward_workspace_setup';

    case STRIPE_PURCHASE = 'stripe_purchase';

    public function getAmount(): int
    {
        return match ($this) {
            // Facturé
            self::SCREENING_SEARCH => 1,
            self::SCREENING_BULK_SEARCH => 1,
            self::SCREENING_MONITORING_START => 5,
            self::SCREENING_MONITORING_RENEWAL => 5,
            self::SCREENING_ALERT_REVIEW => 1,

            self::KYC_ANALYSIS => 2,
            self::KYC_REVIEW => 3,
            self::KYC_DOCUMENT_EXTRACTION => 1,
            self::KYC_RISK_RECHECK => 1,

            self::PDF_GENERATION => 0,
            self::EXPORT_CSV => 0,
            self::AUDIT_LOG_EXPORT => 0,

            // Support / comptable
            self::MANUAL_ADJUSTMENT => 0,
            self::REFUND => 0,
            self::PROMO_CREDIT => 100,
            self::RETENTION_BONUS => 0,

            // Rewards
            self::REWARD_ONBOARDING_COMPLETED => 5,
            self::REWARD_COMPLETE_PROFILE => 2,
            self::REWARD_ENABLE_2FA => 3,
            self::REWARD_INVITE_COLLEAGUE => 10,
            self::REWARD_ADD_CREDIT_CARD => 5,
            self::REWARD_FIRST_SEARCH => 1,
            self::REWARD_WORKSPACE_SETUP => 3,
            self::STRIPE_PURCHASE => 0,
        };
    }

    public function isReward(): bool
    {
        return str_starts_with($this->value, 'reward_');
    }

    public function isBillingAction(): bool
    {
        return match ($this) {
            self::SCREENING_SEARCH,
            self::SCREENING_BULK_SEARCH,
            self::SCREENING_MONITORING_START,
            self::SCREENING_MONITORING_RENEWAL,
            self::SCREENING_ALERT_REVIEW,
            self::KYC_ANALYSIS,
            self::KYC_REVIEW,
            self::KYC_DOCUMENT_EXTRACTION,
            self::KYC_RISK_RECHECK,
            self::PDF_GENERATION,
            self::EXPORT_CSV,
            self::AUDIT_LOG_EXPORT => true,

            default => false,
        };
    }

    public function isSupportAction(): bool
    {
        return match ($this) {
            self::MANUAL_ADJUSTMENT,
            self::REFUND,
            self::PROMO_CREDIT,
            self::RETENTION_BONUS => true,

            default => false,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::SCREENING_SEARCH => 'Recherche LCB-FT',
            self::SCREENING_BULK_SEARCH => 'Recherche LCB-FT en lot',
            self::SCREENING_MONITORING_START => 'Démarrage de surveillance',
            self::SCREENING_MONITORING_RENEWAL => 'Renouvellement de surveillance',
            self::SCREENING_ALERT_REVIEW => 'Revue d’alerte',

            self::KYC_ANALYSIS => 'Analyse KYC',
            self::KYC_REVIEW => 'Revue KYC manuelle',
            self::KYC_DOCUMENT_EXTRACTION => 'Extraction de document KYC',
            self::KYC_RISK_RECHECK => 'Recontrôle du risque KYC',

            self::PDF_GENERATION => 'Génération de rapport PDF',
            self::EXPORT_CSV => 'Export CSV',
            self::AUDIT_LOG_EXPORT => 'Export du journal d’audit',

            self::MANUAL_ADJUSTMENT => 'Ajustement manuel',
            self::REFUND => 'Remboursement',
            self::PROMO_CREDIT => 'Crédit promotionnel',
            self::RETENTION_BONUS => 'Bonus de fidélisation',

            self::REWARD_ONBOARDING_COMPLETED => 'Onboarding terminé',
            self::REWARD_COMPLETE_PROFILE => 'Profil complété',
            self::REWARD_ENABLE_2FA => 'Activation de la double authentification',
            self::REWARD_INVITE_COLLEAGUE => 'Invitation d’un collègue',
            self::REWARD_ADD_CREDIT_CARD => 'Ajout d’une carte bancaire',
            self::REWARD_FIRST_SEARCH => 'Première recherche offerte',
            self::REWARD_WORKSPACE_SETUP => 'Workspace configuré',
            self::STRIPE_PURCHASE => 'Rechargement de crédits',
        };
    }
}
