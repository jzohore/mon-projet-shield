<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enum;

enum ErrorCode: string
{
    // User & Auth
    case USER_NOT_FOUND = 'USER_NOT_FOUND';
    case USER_ALREADY_EXISTS = 'USER_ALREADY_EXISTS';
    case INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';
    case PROFILE_NOT_FOUND = 'PROFILE_NOT_FOUND';

    // Workspace & Members
    case WORKSPACE_NOT_FOUND = 'WORKSPACE_NOT_FOUND';
    case WORKSPACE_INVITATION_NOT_FOUND = 'WORKSPACE_INVITATION_NOT_FOUND';
    case WORKSPACE_HAS_PENDING_INVITATION = 'WORKSPACE_HAS_PENDING_INVITATION';
    case INVITATION_TOKEN_NOT_FOUND = 'INVITATION_TOKEN_NOT_FOUND';
    case WORKSPACE_INVITATION_ALREADY_EXISTS = 'WORKSPACE_INVITATION_ALREADY_EXISTS';
    case WORKSPACE_NAME_ALREADY_EXISTS = 'WORKSPACE_NAME_ALREADY_EXISTS';
    case WORKSPACE_SIRET_ALREADY_EXISTS = 'WORKSPACE_SIRET_ALREADY_EXISTS';
    case WORKSPACE_SIREN_ALREADY_EXISTS = 'WORKSPACE_SIREN_ALREADY_EXISTS';
    case INVALID_WORKSPACE_TYPE = 'INVALID_WORKSPACE_TYPE';
    case MEMBER_NOT_FOUND = 'MEMBER_NOT_FOUND';
    case CANNOT_REVOKE_OWNER = 'CANNOT_REVOKE_OWNER';

    // Compliance & KYC
    case COMPLIANCE_FOLDER_NOT_FOUND = 'COMPLIANCE_FOLDER_NOT_FOUND';

    case CANNOT_ATTACH_GEMINI_OUTPUT = 'CANNOT_ATTACH_GEMINI_OUTPUT';
    case KYC_ALREADY_VALIDATED = 'KYC_ALREADY_VALIDATED';
    case KYC_FOLDER_NOT_IN_REVIEW = 'KYC_FOLDER_NOT_IN_REVIEW';
    case KYC_MISSING_DOCUMENTS = 'KYC_MISSING_DOCUMENTS';
    case UNSUPPORTED_FOLDER_TYPE = 'UNSUPPORTED_FOLDER_TYPE';
    case CANNOT_DELETE_ACTIVE_FOLDER = 'CANNOT_DELETE_ACTIVE_FOLDER';
    case DER_ALREADY_SENT = 'DER_ALREADY_SENT';

    // Documents
    case COMPLIANCE_DOCUMENT_ALREADY_EXISTS = 'COMPLIANCE_DOCUMENT_ALREADY_EXISTS';
    case COMPLIANCE_DOCUMENT_NOT_FOUND = 'COMPLIANCE_DOCUMENT_NOT_FOUND';
    case COMPLIANCE_DOCUMENT_INVALID_FOLDER = 'COMPLIANCE_DOCUMENT_INVALID_FOLDER';
    case COMPLIANCE_DOCUMENT_MANDATORY_DELETION = 'COMPLIANCE_DOCUMENT_MANDATORY_DELETION';

    // Billing & Limits
    case SUBSCRIPTION_NOT_FOUND = 'SUBSCRIPTION_NOT_FOUND';
    case PRODUCT_NOT_FOUND = 'PRODUCT_NOT_FOUND';
    case QUOTA_EXCEEDED = 'QUOTA_EXCEEDED';

    // Audit
    case AUDIT_NOT_FOUND = 'AUDIT_NOT_FOUND';

    /**
     * Retourne un message d'erreur clair, institutionnel et adapté au domaine LCB-FT / CGP.
     */
    public function getLabel(): string
    {
        return match ($this) {
            // Utilisateurs & Authentification
            self::USER_NOT_FOUND => 'Compte introuvable ou inexistant.',
            self::USER_ALREADY_EXISTS => 'Un compte professionnel est déjà associé à cette adresse email.',
            self::INVALID_CREDENTIALS => 'Identifiants de connexion incorrects.',
            self::PROFILE_NOT_FOUND => 'Le profil de conformité associé est introuvable.',

            // Espace de travail (Cabinet) & Collaborateurs
            self::WORKSPACE_NOT_FOUND => 'L\'espace de travail demandé est introuvable.',
            self::WORKSPACE_INVITATION_NOT_FOUND => 'L\'invitation à cet espace de travail est introuvable ou a été annulée.',
            self::WORKSPACE_HAS_PENDING_INVITATION => 'Une invitation est déjà en attente de validation pour ce destinataire.',
            self::INVITATION_TOKEN_NOT_FOUND => 'Le lien d\'invitation est invalide ou a expiré.',
            self::WORKSPACE_INVITATION_ALREADY_EXISTS => 'Ce professionnel dispose déjà d\'une invitation active pour cet espace.',
            self::WORKSPACE_NAME_ALREADY_EXISTS => 'Un cabinet ou espace de travail porte déjà ce nom.',
            self::WORKSPACE_SIRET_ALREADY_EXISTS => 'Un espace de travail existe déjà avec ce numéro SIRET.',
            self::WORKSPACE_SIREN_ALREADY_EXISTS => 'Un espace de travail existe déjà avec ce numéro SIREN.',
            self::INVALID_WORKSPACE_TYPE => 'Le statut juridique ou le type de structure sélectionné est invalide.',
            self::MEMBER_NOT_FOUND => 'Ce collaborateur ne fait pas partie de cet espace de travail.',
            self::CANNOT_REVOKE_OWNER => 'Opération interdite : impossible de révoquer les droits de l\'administrateur principal.',

            // Conformité, LCB-FT & KYC
            self::COMPLIANCE_FOLDER_NOT_FOUND => 'Le dossier de conformité demandé est introuvable.',
            self::CANNOT_ATTACH_GEMINI_OUTPUT => 'Le rapport IA est déjà attaché à cet enregistrement.',
            self::KYC_ALREADY_VALIDATED => 'Ce dossier de conformité est validé et ne peut plus subir de modifications.',
            self::KYC_FOLDER_NOT_IN_REVIEW => 'Ce dossier ne peut pas être approuvé car il n\'est pas actuellement en cours d\'analyse.',
            self::KYC_MISSING_DOCUMENTS => 'Soumission impossible : des pièces justificatives obligatoires sont manquantes.',
            self::UNSUPPORTED_FOLDER_TYPE => 'Le type de dossier sélectionné ne respecte pas les modèles de conformité supportés.',
            self::CANNOT_DELETE_ACTIVE_FOLDER => 'Impossible de supprimer un dossier de conformité en cours d\'instruction.',
            self::DER_ALREADY_SENT => 'Le document d\'entrée en relation (DER) a déjà été transmis à l\'investisseur.',

            // Pièces & Documents
            self::COMPLIANCE_DOCUMENT_ALREADY_EXISTS => 'Cette pièce justificative a déjà été ajoutée au dossier.',
            self::COMPLIANCE_DOCUMENT_NOT_FOUND => 'Le document spécifié est introuvable.',
            self::COMPLIANCE_DOCUMENT_INVALID_FOLDER => 'Le document spécifié n\'est pas rattaché à ce dossier de conformité.',
            self::COMPLIANCE_DOCUMENT_MANDATORY_DELETION => 'Suppression impossible : ce document est exigé par la réglementation.',

            // Forfaits & Abonnements
            self::SUBSCRIPTION_NOT_FOUND => 'Aucun abonnement actif associé à cet espace de travail.',
            self::PRODUCT_NOT_FOUND => 'L\'offre ou le module sélectionné est indisponible.',
            self::QUOTA_EXCEEDED => 'Quota atteint : votre forfait actuel ne permet pas d\'exécuter cette vérification supplémentaire.',

            // Traçabilité & Audits
            self::AUDIT_NOT_FOUND => 'La piste d\'audit ou le rapport de vérification demandé est introuvable.',
        };
    }
}
