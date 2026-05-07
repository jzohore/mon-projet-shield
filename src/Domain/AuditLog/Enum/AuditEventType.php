<?php

namespace App\Domain\AuditLog\Enum;

enum AuditEventType: string
{
    // --- UTILISATEUR & SÉCURITÉ ---
    case USER_REGISTERED = 'user.registered';
    case USER_MAGIC_LINK_REQUESTED = 'user.magic_link_requested';
    case USER_LOGGED_IN = 'user.logged_in';
    case USER_LOGGED_OUT = 'user.logged_out';
    case USER_PROFILE_UPDATED = 'user.profile_updated';
    case DEVICE_CREATED = 'device.created';
    case SUSPICIOUS_LOGIN_ATTEMPT = 'security.suspicious_login';

    // --- WORKSPACE (CABINET) ---
    case WORKSPACE_CREATED = 'workspace.created';
    case WORKSPACE_MEMBER_ADDED = 'workspace.member_added';
    case WORKSPACE_MEMBER_REVOKED = 'workspace.member_revoked';
    case ONBOARDING_COMPLETED = 'workspace.onboarding_completed';

    // --- DOSSIER KYC (LE CONTENEUR) ---
    case KYC_FOLDER_INITIATED = 'kyc_folder.initiated';
    case KYC_PORTAL_ACCESSED = 'kyc_folder.portal_accessed';
    case KYC_COMPANY_BOUND = 'kyc_folder.company_bound';
    case KYC_FOLDER_SUBMITTED = 'kyc_folder.submitted';
    case KYC_FOLDER_APPROVED = 'kyc_folder.approved';
    case KYC_FOLDER_REJECTED = 'kyc_folder.rejected';

    // --- INTERVENANTS KYC (STAKEHOLDERS) ---
    case KYC_STAKEHOLDER_ADDED = 'kyc_stakeholder.added';
    case KYC_STAKEHOLDER_REMOVED = 'kyc_stakeholder.removed';

    // --- DOCUMENTS KYC (PIÈCES JUSTIFICATIVES) ---
    case KYC_DOCUMENT_REQUESTED = 'kyc_document.requested';
    case KYC_DOCUMENT_UPLOADED = 'kyc_document.uploaded';
    case KYC_DOCUMENT_OCR = 'kyc_document.ocr';
    case KYC_DOCUMENT_VALIDATED = 'kyc_document.validated';
    case KYC_DOCUMENT_REJECTED = 'kyc_document.rejected';
    case KYC_DOCUMENT_EXPIRED = 'kyc_document.expired';

    // --- BILLING & CRÉDITS ---
    case CREDIT_PURCHASED = 'billing.credit_purchased';
    case CREDIT_CONSUMED = 'billing.credit_consumed';
    case SUBSCRIPTION_ACTIVATED = 'subscription_activated';
    case SUBSCRIPTION_CANCELED = 'subscription_canceled';

    // --- SERVICES & ANALYSES ---
    case SCREENING_PERFORMED = 'screening.performed';
    case ADVISORY_REPORT_GENERATED = 'advisory.generated';
    case DOCUMENT_SHARED = 'document_shared';

    /**
     * Retourne un libellé lisible par un humain.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::USER_REGISTERED => 'Inscription de l\'utilisateur',
            self::USER_MAGIC_LINK_REQUESTED => 'Demande de lien de connexion',
            self::USER_LOGGED_IN => 'Connexion réussie',
            self::USER_LOGGED_OUT => 'Déconnexion',
            self::USER_PROFILE_UPDATED => 'Mise à jour du profil',
            self::DEVICE_CREATED => 'Nouvel appareil enregistré',
            self::SUSPICIOUS_LOGIN_ATTEMPT => 'Tentative de connexion suspecte',

            self::WORKSPACE_CREATED => 'Création de l\'espace de travail',
            self::WORKSPACE_MEMBER_ADDED => 'Invitation d\'un collaborateur',
            self::WORKSPACE_MEMBER_REVOKED => 'Révocation d\'un collaborateur',
            self::ONBOARDING_COMPLETED => 'Fin de la configuration initiale',

            self::KYC_FOLDER_INITIATED => 'Création de la demande KYC',
            self::KYC_PORTAL_ACCESSED => 'Ouverture du portail par le client',
            self::KYC_COMPANY_BOUND => 'Identification de la société',
            self::KYC_FOLDER_SUBMITTED => 'Soumission du dossier par le client',
            self::KYC_FOLDER_APPROVED => 'Approbation finale du dossier LCB-FT',
            self::KYC_FOLDER_REJECTED => 'Rejet global du dossier',

            self::KYC_STAKEHOLDER_ADDED => 'Ajout d\'un intervenant',
            self::KYC_STAKEHOLDER_REMOVED => 'Suppression d\'un intervenant',

            self::KYC_DOCUMENT_REQUESTED => 'Nouvelle pièce requise',
            self::KYC_DOCUMENT_UPLOADED => 'Dépôt d\'un document',
            self::KYC_DOCUMENT_OCR => 'Validation OCR',
            self::KYC_DOCUMENT_VALIDATED => 'Validation d\'une pièce',
            self::KYC_DOCUMENT_REJECTED => 'Refus d\'une pièce',
            self::KYC_DOCUMENT_EXPIRED => 'Expiration d\'une pièce',

            self::CREDIT_PURCHASED => 'Achat de crédits d\'audit',
            self::CREDIT_CONSUMED => 'Consommation de crédits',
            self::SUBSCRIPTION_ACTIVATED => 'Abonnement activé',
            self::SUBSCRIPTION_CANCELED => 'Abonnement annulé',

            self::SCREENING_PERFORMED => 'Recherche LCB-FT effectuée',
            self::ADVISORY_REPORT_GENERATED => 'Génération du rapport Advisory',
            self::DOCUMENT_SHARED => 'Partage d\'un document',
        };
    }

    /**
     * Retourne l'icône Lucide associée à l'événement.
     */
    public function getIcon(): string
    {
        return match ($this) {
            // Utilisateur
            self::USER_REGISTERED, self::WORKSPACE_MEMBER_ADDED, self::KYC_STAKEHOLDER_ADDED => 'lucide:user-plus',
            self::USER_MAGIC_LINK_REQUESTED => 'lucide:wand-2',
            self::USER_LOGGED_IN => 'lucide:log-in',
            self::USER_LOGGED_OUT => 'lucide:log-out',
            self::USER_PROFILE_UPDATED => 'lucide:user-cog',
            self::DEVICE_CREATED => 'lucide:smartphone',
            self::SUSPICIOUS_LOGIN_ATTEMPT => 'lucide:shield-alert',

            // Workspace
            self::WORKSPACE_CREATED => 'lucide:building-2',
            self::WORKSPACE_MEMBER_REVOKED, self::KYC_STAKEHOLDER_REMOVED => 'lucide:user-minus',
            self::ONBOARDING_COMPLETED => 'lucide:party-popper',

            // KYC Folder
            self::KYC_FOLDER_INITIATED => 'lucide:folder-plus',
            self::KYC_PORTAL_ACCESSED => 'lucide:folder-open',
            self::KYC_COMPANY_BOUND => 'lucide:briefcase',
            self::KYC_FOLDER_SUBMITTED => 'lucide:send',
            self::KYC_FOLDER_APPROVED => 'lucide:folder-check',
            self::KYC_FOLDER_REJECTED => 'lucide:folder-x',

            // Stakeholders
            // Documents
            self::KYC_DOCUMENT_REQUESTED => 'lucide:file-question',
            self::KYC_DOCUMENT_UPLOADED => 'lucide:file-up',
            self::KYC_DOCUMENT_OCR => 'lucide:scan-text',
            self::KYC_DOCUMENT_VALIDATED => 'lucide:file-check-2',
            self::KYC_DOCUMENT_REJECTED => 'lucide:file-x-2',
            self::KYC_DOCUMENT_EXPIRED => 'lucide:file-clock',

            // Billing & Subs
            self::CREDIT_PURCHASED => 'lucide:coins',
            self::CREDIT_CONSUMED => 'lucide:receipt',
            self::SUBSCRIPTION_ACTIVATED => 'lucide:check-circle',
            self::SUBSCRIPTION_CANCELED => 'lucide:x-octagon',

            // Services
            self::SCREENING_PERFORMED => 'lucide:search-check',
            self::ADVISORY_REPORT_GENERATED => 'lucide:file-bar-chart-2',
            self::DOCUMENT_SHARED => 'lucide:share-2',
        };
    }

    /**
     * Retourne la classe de couleur Tailwind pour styliser l'icône/le badge.
     */
    public function getColorClass(): string
    {
        return match ($this) {
            // Positif / Succès (Émeraude)
            self::USER_LOGGED_IN,
            self::ONBOARDING_COMPLETED,
            self::KYC_FOLDER_SUBMITTED,
            self::KYC_FOLDER_APPROVED,
            self::KYC_DOCUMENT_VALIDATED,
            self::CREDIT_PURCHASED,
            self::SUBSCRIPTION_ACTIVATED => 'text-emerald-500',

            // Actions structurantes (Bleu / Indigo)
            self::USER_REGISTERED,
            self::WORKSPACE_CREATED,
            self::WORKSPACE_MEMBER_ADDED,
            self::KYC_PORTAL_ACCESSED,
            self::KYC_COMPANY_BOUND,
            self::KYC_DOCUMENT_UPLOADED,
            self::DOCUMENT_SHARED => 'text-blue-500',

            // Avertissements / Attente (Ambre)
            self::WORKSPACE_MEMBER_REVOKED,
            self::KYC_DOCUMENT_REQUESTED,
            self::KYC_DOCUMENT_EXPIRED => 'text-amber-500',

            // Danger / Rejets (Rouge)
            self::SUSPICIOUS_LOGIN_ATTEMPT,
            self::KYC_FOLDER_REJECTED,
            self::KYC_DOCUMENT_REJECTED,
            self::SUBSCRIPTION_CANCELED => 'text-red-500',

            // Actions Métier expertes (Violet)
            self::USER_MAGIC_LINK_REQUESTED,
            self::KYC_FOLDER_INITIATED,
            self::KYC_DOCUMENT_OCR,
            self::SCREENING_PERFORMED,
            self::ADVISORY_REPORT_GENERATED => 'text-violet-600',

            // Actions neutres / Passives (Gris Slate)
            self::USER_LOGGED_OUT,
            self::USER_PROFILE_UPDATED,
            self::DEVICE_CREATED,
            self::KYC_STAKEHOLDER_ADDED,
            self::KYC_STAKEHOLDER_REMOVED,
            self::CREDIT_CONSUMED => 'text-slate-400',
        };
    }
}
