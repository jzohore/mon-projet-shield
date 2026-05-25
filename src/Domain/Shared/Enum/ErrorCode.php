<?php

namespace App\Domain\Shared\Enum;

enum ErrorCode: string
{
    case WORKSPACE_NOT_FOUND = 'WORKSPACE_NOT_FOUND';
    case WORKSPACE_INVITATION_NOT_FOUND = 'WORKSPACE_INVITATION_NOT_FOUND';
    case WORKSPACE_HAS_PENDING_INVITATION = 'WORKSPACE_HAS_PENDING_INVITATION';
    case INVITATION_TOKEN_NOT_FOUND = 'INVITATION_TOKEN_NOT_FOUND';
    case WORKSPACE_INVITATION_ALREADY_EXIST = 'WORKSPACE_INVITATION_ALREADY_EXIST';
    case WORKSPACE_NAME_ALREADY_EXISTS = 'WORKSPACE_NAME_ALREADY_EXISTS';
    case WORKSPACE_TYPE_FOUND = 'WORKSPACE_TYPE_FOUND';
    case KYC_ALREADY_VALIDATED = 'KYC_ALREADY_VALIDATED';
    case INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';
    case USER_NOT_FOUND = 'USER_NOT_FOUND';
    case SUBSCRIPTION_NOT_FOUND = 'SUBSCRIPTION_NOT_FOUND';
    case PRODUCT_NOT_FOUND = 'PRODUCT_NOT_FOUND';
    case KYC_FOLDER_NOT_IN_REVIEW = 'KYC_FOLDER_NOT_IN_REVIEW';
    case KYC_MISSING_DOCUMENTS = 'KYC_MISSING_DOCUMENTS';

    case UNSUPPORTED_FOLDER_TYPE = 'UNSUPPORTED_FOLDER_TYPE';
    case CANNOT_DELETE_ACTIVE_FOLDER = 'CANNOT_DELETE_ACTIVE_FOLDER';

    case AUDIT_NOT_FOUND = 'AUDIT_NOT_FOUND';
    case WORKSPACE_SIRET_ALREADY_EXISTS = 'WORKSPACE_SIRET_ALREADY_EXISTS';
    case MEMBER_NOT_FOUND = 'MEMBER_NOT_FOUND';
    case CANNOT_REVOKE_OWNER = 'CANNOT_REVOKE_OWNER';
    case COMPLIANCE_FOLDER_NOT_FOUND = 'COMPLIANCE_FOLDER_NOT_FOUND';

    /**
     * Retourne un message d'erreur clair et "User-friendly"
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::USER_NOT_FOUND           => 'L\'utilisateur demandé est introuvable.',
            self::INVALID_CREDENTIALS      => 'Les identifiants de connexion sont incorrects.',
            self::WORKSPACE_NOT_FOUND      => 'L\'espace de travail demandé n\'existe pas.',
            self::WORKSPACE_INVITATION_NOT_FOUND      => 'L\'invitation à l\'espace de travail demandé n\'existe pas.',
            self::WORKSPACE_HAS_PENDING_INVITATION => 'Une invitation est déjà en attente.',
            self::WORKSPACE_INVITATION_ALREADY_EXIST => 'Cette action est impossible car l\'utilisation existe déjà.',
            self::WORKSPACE_TYPE_FOUND      => 'Type de structure "%s" invalide ou indisponible.',
            self::SUBSCRIPTION_NOT_FOUND   => 'Aucun abonnement actif n\'a été trouvé.',
            self::PRODUCT_NOT_FOUND        => 'Le produit demandé est indisponible.',
            self::KYC_ALREADY_VALIDATED    => 'Cette action est impossible car le dossier est déjà validé.',
            self::KYC_FOLDER_NOT_IN_REVIEW => 'Ce dossier ne peut pas être approuvé car il n\'est pas en phase d\'analyse.',
            self::KYC_MISSING_DOCUMENTS    => 'Impossible de soumettre : des documents obligatoires sont manquants.',
            self::UNSUPPORTED_FOLDER_TYPE  => 'Le type de dossier demandé n\'est pas valide.',
            self::CANNOT_DELETE_ACTIVE_FOLDER => 'La suppression est impossible pour cette action.',
            self::AUDIT_NOT_FOUND => 'L\'audit demandé est introuvable.',
            self::WORKSPACE_NAME_ALREADY_EXISTS => 'Cette action est impossible car ce workspace avec se nom existe déjà.',
            self::WORKSPACE_SIRET_ALREADY_EXISTS => 'Cette action est impossible car ce workspace avec se siret existe déjà.',
            self::INVITATION_TOKEN_NOT_FOUND => 'Invitation introuvable ou expirée.',
            self::MEMBER_NOT_FOUND => 'Membre introuvable dans cet espace',
            self::CANNOT_REVOKE_OWNER => 'Impossible d\'effectuer cette action.',
            self::COMPLIANCE_FOLDER_NOT_FOUND => 'Dossier de conformité introuvable',
        };
    }
}
