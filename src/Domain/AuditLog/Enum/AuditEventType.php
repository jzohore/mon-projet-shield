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
    case KYC_FOLDER_INITIATED = 'kyc_folder.initiated';           // L'avocat crée la demande
    case KYC_PORTAL_ACCESSED = 'kyc_folder.portal_accessed';      // Le client a cliqué sur son lien magique
    case KYC_COMPANY_BOUND = 'kyc_folder.company_bound';          // Le client a validé sa société via Sirene
    case KYC_FOLDER_SUBMITTED = 'kyc_folder.submitted';           // Le client a fini et signé l'attestation
    case KYC_FOLDER_APPROVED = 'kyc_folder.approved';             // L'avocat a validé tout le dossier LCB-FT
    case KYC_FOLDER_REJECTED = 'kyc_folder.rejected';             // L'avocat a refusé le dossier complet

    // --- INTERVENANTS KYC (STAKEHOLDERS) ---
    case KYC_STAKEHOLDER_ADDED = 'kyc_stakeholder.added';         // Un dirigeant ou BE a été déclaré
    case KYC_STAKEHOLDER_REMOVED = 'kyc_stakeholder.removed';     // Un intervenant a été supprimé

    // --- DOCUMENTS KYC (PIÈCES JUSTIFICATIVES) ---
    case KYC_DOCUMENT_REQUESTED = 'kyc_document.requested';       // Le système a généré une "case vide" (ex: KBIS)
    case KYC_DOCUMENT_UPLOADED = 'kyc_document.uploaded';         // Le client a déposé un fichier
    case KYC_DOCUMENT_OCR = 'kyc_document.ocr';         // Le client a déposé un fichier
    case KYC_DOCUMENT_VALIDATED = 'kyc_document.validated';       // L'avocat a examiné et validé la pièce
    case KYC_DOCUMENT_REJECTED = 'kyc_document.rejected';         // L'avocat a refusé la pièce (floue, périmée...)
    case KYC_DOCUMENT_EXPIRED = 'kyc_document.expired';           // CRON Job : Le document a atteint sa date d'expiration

    // --- BILLING & CRÉDITS ---
    case CREDIT_PURCHASED = 'billing.credit_purchased'; // Achat via Stripe
    case CREDIT_CONSUMED = 'billing.credit_consumed';

    /**
     * Retourne un libellé lisible par un humain pour l'affichage dans la frise chronologique (Audit Trail).
     */
    public function getLabel(): string
    {
        return match ($this) {
            // Utilisateurs
            self::USER_REGISTERED => 'Inscription de l\'utilisateur',
            self::USER_MAGIC_LINK_REQUESTED => 'Demande de lien de connexion',
            self::USER_LOGGED_IN => 'Connexion réussie',
            self::USER_LOGGED_OUT => 'Déconnexion',
            self::USER_PROFILE_UPDATED => 'Mise à jour du profil',
            self::DEVICE_CREATED => 'Nouvel appareil enregistré',
            self::SUSPICIOUS_LOGIN_ATTEMPT => 'Tentative de connexion suspecte',

            // Workspace
            self::WORKSPACE_CREATED => 'Création de l\'espace de travail',
            self::WORKSPACE_MEMBER_ADDED => 'Invitation d\'un collaborateur',
            self::WORKSPACE_MEMBER_REVOKED => 'Révocation d\'un collaborateur',
            self::ONBOARDING_COMPLETED => 'Fin de la configuration initiale',

            // Dossier KYC
            self::KYC_FOLDER_INITIATED => 'Création de la demande KYC',
            self::KYC_PORTAL_ACCESSED => 'Ouverture du portail par le client',
            self::KYC_COMPANY_BOUND => 'Identification de la société',
            self::KYC_FOLDER_SUBMITTED => 'Soumission du dossier par le client',
            self::KYC_FOLDER_APPROVED => 'Approbation finale du dossier LCB-FT',
            self::KYC_FOLDER_REJECTED => 'Rejet global du dossier',

            // Stakeholders
            self::KYC_STAKEHOLDER_ADDED => 'Ajout d\'un intervenant',
            self::KYC_STAKEHOLDER_REMOVED => 'Suppression d\'un intervenant',

            // Documents
            self::KYC_DOCUMENT_REQUESTED => 'Nouvelle pièce requise',
            self::KYC_DOCUMENT_UPLOADED => 'Dépôt d\'un document',
            self::KYC_DOCUMENT_VALIDATED => 'Validation d\'une pièce',
            self::KYC_DOCUMENT_REJECTED => 'Refus d\'une pièce',
            self::KYC_DOCUMENT_EXPIRED => 'Expiration d\'une pièce',
            self::KYC_DOCUMENT_OCR => 'Validation OCR',

            // Billing
            self::CREDIT_PURCHASED => 'Achat de crédits d\'audit',
            self::CREDIT_CONSUMED => 'Consommation de crédits',
        };
    }
}
