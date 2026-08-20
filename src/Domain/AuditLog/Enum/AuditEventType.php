<?php

declare(strict_types=1);

namespace App\Domain\AuditLog\Enum;

/**
 * Énumération centrale de tous les événements traçables de l'application KYSURE.
 * Garantit la conformité et l'irréfutabilité exigées par l'AMF et l'ACPR.
 */
enum AuditEventType: string
{
    // --- UTILISATEUR & SÉCURITÉ ---
    case USER_REGISTERED = 'security.user_registered';
    case USER_MAGIC_LINK_REQUESTED = 'security.magic_link_requested';
    case USER_LOGGED_IN = 'security.user_logged_in';
    case USER_LOGGED_OUT = 'security.user_logged_out';
    case USER_PROFILE_UPDATED = 'security.profile_updated';
    case DEVICE_CREATED = 'security.device_created';
    case SUSPICIOUS_LOGIN_ATTEMPT = 'security.suspicious_login';
    case ADMIN_IMPERSONATION_START = 'security.impersonation_start';
    case ADMIN_IMPERSONATION_EXIT = 'security.impersonation_exit';

    // --- WORKSPACE (CABINET) ---
    case WORKSPACE_CREATED = 'workspace.created';
    case WORKSPACE_UPDATED = 'workspace.updated';
    case WORKSPACE_SUSPENDED = 'workspace.suspended';
    case WORKSPACE_MEMBER_ADDED = 'workspace.member_added';
    case WORKSPACE_MEMBER_REVOKED = 'workspace.member_revoked';
    case ONBOARDING_COMPLETED = 'workspace.onboarding_completed';

    // --- REGISTRATIONS & VÉRIFICATIONS LÉGALES ---
    case SIRET_CHECK_SUCCESS = 'compliance.siret_check_success';
    case SIRET_CHECK_FAILED = 'compliance.siret_check_failed';
    case ORIAS_CHECK_SUCCESS = 'compliance.orias_check_success';
    case ORIAS_CHECK_FAILED = 'compliance.orias_check_failed';

    // --- DOSSIERS KYC & CONFORMITÉ LCB-FT ---
    case KYC_FOLDER_INITIATED = 'kyc.folder_initiated';
    case KYC_PORTAL_ACCESSED = 'kyc.portal_accessed';
    case KYC_COMPANY_BOUND = 'kyc.company_bound';
    case KYC_FOLDER_SUBMITTED = 'kyc.folder_submitted';
    case KYC_FOLDER_APPROVED = 'kyc.folder_approved';
    case KYC_FOLDER_REJECTED = 'kyc.folder_rejected';

    // --- INTERVENANTS KYC (STAKEHOLDERS / UBO) ---
    case KYC_STAKEHOLDER_ADDED = 'kyc.stakeholder_added';
    case KYC_STAKEHOLDER_REMOVED = 'kyc.stakeholder_removed';

    // --- DOCUMENTS KYC (PIÈCES JUSTIFICATIVES) ---
    case KYC_DOCUMENT_REQUESTED = 'document.requested';
    case KYC_DOCUMENT_UPLOADED = 'document.uploaded';
    case KYC_DOCUMENT_OCR_PROCESSED = 'document.ocr_processed';
    case KYC_DOCUMENT_VALIDATED = 'document.validated';
    case KYC_DOCUMENT_REJECTED = 'document.rejected';
    case KYC_DOCUMENT_EXPIRED = 'document.expired';
    case DOCUMENT_SHARED = 'document.shared';

    // --- CONFORMITÉ DER & SIGNATURE ÉLECTRONIQUE ---
    case DER_GENERATED = 'der.generated';
    case DER_OPENED = 'der.opened';
    case DER_SIGNED = 'der.signed';
    case SIGNATURE_UPDATED = 'der.signature_updated';

    case ACCEPTED_RECORDING = 'ac.recording';

    // --- ANALYSES & SCREENING (SANCTIONS / PPE) ---
    case SCREENING_PERFORMED = 'screening.performed';
    case ADVISORY_REPORT_GENERATED = 'advisory.generated';

    // --- FACTURATION & ABONNEMENT STRIPE ---
    case SUBSCRIPTION_ACTIVATED = 'billing.subscription_activated';
    case SUBSCRIPTION_CANCELED = 'billing.subscription_canceled';
    case SUBSCRIPTION_TRIAL_EXTENDED = 'billing.trial_extended';

    /**
     * Libellé explicite en français pour l'affichage dans les journaux d'audit.
     */
    public function getLabel(): string
    {
        return match ($this) {
            // Sécurité
            self::USER_REGISTERED => 'Inscription de l\'utilisateur',
            self::USER_MAGIC_LINK_REQUESTED => 'Demande de lien de connexion',
            self::USER_LOGGED_IN => 'Connexion réussie',
            self::USER_LOGGED_OUT => 'Déconnexion',
            self::USER_PROFILE_UPDATED => 'Mise à jour du profil',
            self::DEVICE_CREATED => 'Nouvel appareil enregistré',
            self::SUSPICIOUS_LOGIN_ATTEMPT => 'Tentative de connexion suspecte',
            self::ADMIN_IMPERSONATION_START => 'Connexion support (Impersonation)',
            self::ADMIN_IMPERSONATION_EXIT => 'Fin de connexion support',

            // Workspace
            self::WORKSPACE_CREATED => 'Création du cabinet',
            self::WORKSPACE_UPDATED => 'Modification de la fiche du cabinet',
            self::WORKSPACE_SUSPENDED => 'Suspension du cabinet',
            self::WORKSPACE_MEMBER_ADDED => 'Ajout d\'un collaborateur',
            self::WORKSPACE_MEMBER_REVOKED => 'Révocation d\'un collaborateur',
            self::ONBOARDING_COMPLETED => 'Configuration initiale terminée',

            // Vérifications Légales
            self::SIRET_CHECK_SUCCESS => 'Validation SIRET (INSEE)',
            self::SIRET_CHECK_FAILED => 'Échec de validation SIRET',
            self::ORIAS_CHECK_SUCCESS => 'Validation agrément ORIAS',
            self::ORIAS_CHECK_FAILED => 'Échec de validation ORIAS',

            // KYC
            self::KYC_FOLDER_INITIATED => 'Création du dossier de conformité',
            self::KYC_PORTAL_ACCESSED => 'Ouverture du portail par le client',
            self::KYC_COMPANY_BOUND => 'Identification de la société',
            self::KYC_FOLDER_SUBMITTED => 'Soumission du dossier par le client',
            self::KYC_FOLDER_APPROVED => 'Validation finale du dossier LCB-FT',
            self::KYC_FOLDER_REJECTED => 'Rejet du dossier LCB-FT',

            // Stakeholders
            self::KYC_STAKEHOLDER_ADDED => 'Ajout d\'un bénéficiaire effectif (UBO)',
            self::KYC_STAKEHOLDER_REMOVED => 'Suppression d\'un bénéficiaire effectif',

            // Documents
            self::KYC_DOCUMENT_REQUESTED => 'Nouvelle pièce justificative requise',
            self::KYC_DOCUMENT_UPLOADED => 'Dépôt d\'un document client',
            self::KYC_DOCUMENT_OCR_PROCESSED => 'Analyse OCR automatique (Textract)',
            self::KYC_DOCUMENT_VALIDATED => 'Validation d\'un document',
            self::KYC_DOCUMENT_REJECTED => 'Refus d\'un document',
            self::KYC_DOCUMENT_EXPIRED => 'Expiration d\'un document',
            self::DOCUMENT_SHARED => 'Partage d\'un document sécurisé',

            // DER & Signature
            self::DER_GENERATED => 'Génération du Document d\'Entrée en Relation (DER)',
            self::DER_OPENED => 'Consultation du DER par le client',
            self::DER_SIGNED => 'Signature électronique du DER',
            self::SIGNATURE_UPDATED => 'Mise à jour des paramètres de signature',
            self::ACCEPTED_RECORDING => 'Accord pour enregistrer l\'entretien',

            // Screening
            self::SCREENING_PERFORMED => 'Vérification Listes de Sanctions / PPE',
            self::ADVISORY_REPORT_GENERATED => 'Génération du rapport d\'adéquation',

            // Facturation
            self::SUBSCRIPTION_ACTIVATED => 'Activation de l\'abonnement SaaS',
            self::SUBSCRIPTION_CANCELED => 'Résiliation de l\'abonnement',
            self::SUBSCRIPTION_TRIAL_EXTENDED => 'Prolongation de la période d\'essai',
        };
    }

    /**
     * Catégorisation pour le filtrage par onglet dans le back-office KYSURE.
     */
    public function getCategory(): string
    {
        return match ($this) {
            self::USER_REGISTERED,
            self::USER_MAGIC_LINK_REQUESTED,
            self::USER_LOGGED_IN,
            self::USER_LOGGED_OUT,
            self::USER_PROFILE_UPDATED,
            self::DEVICE_CREATED,
            self::SUSPICIOUS_LOGIN_ATTEMPT,
            self::ADMIN_IMPERSONATION_START,
            self::ADMIN_IMPERSONATION_EXIT => 'Sécurité',

            self::WORKSPACE_CREATED,
            self::WORKSPACE_UPDATED,
            self::WORKSPACE_SUSPENDED,
            self::WORKSPACE_MEMBER_ADDED,
            self::WORKSPACE_MEMBER_REVOKED,
            self::ONBOARDING_COMPLETED => 'Cabinet',

            self::SIRET_CHECK_SUCCESS,
            self::SIRET_CHECK_FAILED,
            self::ORIAS_CHECK_SUCCESS,
            self::ORIAS_CHECK_FAILED,
            self::SCREENING_PERFORMED,
            self::ADVISORY_REPORT_GENERATED => 'Agréments & Screening',

            self::KYC_FOLDER_INITIATED,
            self::KYC_PORTAL_ACCESSED,
            self::KYC_COMPANY_BOUND,
            self::KYC_FOLDER_SUBMITTED,
            self::KYC_FOLDER_APPROVED,
            self::KYC_FOLDER_REJECTED,
            self::KYC_STAKEHOLDER_ADDED,
            self::KYC_STAKEHOLDER_REMOVED,
            self::KYC_DOCUMENT_REQUESTED,
            self::KYC_DOCUMENT_UPLOADED,
            self::KYC_DOCUMENT_OCR_PROCESSED,
            self::KYC_DOCUMENT_VALIDATED,
            self::KYC_DOCUMENT_REJECTED,
            self::KYC_DOCUMENT_EXPIRED,
            self::DOCUMENT_SHARED => 'Dossiers LCB-FT',

            self::DER_GENERATED,
            self::DER_OPENED,
            self::DER_SIGNED,
            self::ACCEPTED_RECORDING,
            self::SIGNATURE_UPDATED => 'DER & Signature',

            self::SUBSCRIPTION_ACTIVATED,
            self::SUBSCRIPTION_CANCELED,
            self::SUBSCRIPTION_TRIAL_EXTENDED => 'Abonnement',
        };
    }

    /**
     * Style de badge Tailwind CSS pré-calculé pour une scannabilité immédiate dans Twig.
     */
    public function getBadgeColor(): string
    {
        return match ($this) {
            self::KYC_FOLDER_APPROVED,
            self::DER_SIGNED,
            self::ORIAS_CHECK_SUCCESS,
            self::SIRET_CHECK_SUCCESS,
            self::SUBSCRIPTION_ACTIVATED => 'bg-emerald-50 text-emerald-700 border-emerald-200',

            self::SUSPICIOUS_LOGIN_ATTEMPT,
            self::WORKSPACE_SUSPENDED,
            self::KYC_FOLDER_REJECTED,
            self::KYC_DOCUMENT_REJECTED,
            self::ORIAS_CHECK_FAILED,
            self::SIRET_CHECK_FAILED,
            self::SUBSCRIPTION_CANCELED => 'bg-rose-50 text-rose-700 border-rose-200',

            self::ADMIN_IMPERSONATION_START,
            self::ADMIN_IMPERSONATION_EXIT,
            self::SUBSCRIPTION_TRIAL_EXTENDED => 'bg-amber-50 text-amber-700 border-amber-200',

            self::KYC_DOCUMENT_OCR_PROCESSED,
            self::SCREENING_PERFORMED,
            self::ADVISORY_REPORT_GENERATED => 'bg-indigo-50 text-indigo-700 border-indigo-200',

            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }

    /**
     * Détermine si l'événement nécessite un surlignage critique ou une alerte admin.
     */
    public function isCritical(): bool
    {
        return match ($this) {
            self::SUSPICIOUS_LOGIN_ATTEMPT,
            self::WORKSPACE_SUSPENDED,
            self::ORIAS_CHECK_FAILED,
            self::SIRET_CHECK_FAILED,
            self::KYC_FOLDER_REJECTED => true,
            default => false,
        };
    }

    /**
     * Détermine si l'événement doit être affiché dans le journal du Cabinet utilisateur,
     * ou s'il s'agit d'une métrique interne réservée aux Administrateurs KYSURE.
     */
    public function isVisibleToWorkspace(): bool
    {
        return match ($this) {
            self::USER_MAGIC_LINK_REQUESTED,
            self::USER_LOGGED_OUT,
            self::KYC_DOCUMENT_OCR_PROCESSED,
            self::ONBOARDING_COMPLETED,
            self::ADMIN_IMPERSONATION_START,
            self::ADMIN_IMPERSONATION_EXIT => false,
            default => true,
        };
    }
}
