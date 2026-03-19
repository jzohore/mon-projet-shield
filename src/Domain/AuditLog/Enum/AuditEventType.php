<?php

namespace App\Domain\AuditLog\Enum;

enum AuditEventType: string
{
    // --- UTILISATEUR ---
    case USER_REGISTERED = 'user.registered';
    case USER_MAGIC_LINK_REQUESTED = 'user.magic_link_requested';
    case USER_LOGGED_IN = 'user.logged_in';
    case USER_LOGGED_OUT = 'user.logged_out';
    case USER_PROFILE_UPDATED = 'user.profile_updated'; // 👈 Pour l'étape 2 de l'onboarding

    // --- SÉCURITÉ / DEVICE ---
    case DEVICE_CREATED = 'device.created';
    case SUSPICIOUS_LOGIN_ATTEMPT = 'security.suspicious_login';

    // --- ONBOARDING & WORKSPACE ---
    case WORKSPACE_CREATED = 'workspace.created';             // 👈 L'événement qu'on vient de coder
    case WORKSPACE_MEMBER_ADDED = 'workspace.member_added';   // 👈 Utile pour plus tard (invitations)
    case WORKSPACE_MEMBER_REVOKED = 'workspace.member_revoked';   // 👈 Utile pour plus tard (invitations)
    case ONBOARDING_COMPLETED = 'user.onboarding_completed';  // 👈 Quand le tunnel est 100% fini

    // --- MÉTIER (Exemples) ---
    case DOCUMENT_UPLOADED = 'document.uploaded';
    case KYC_APPROVED = 'kyc.approved';
    case KYC_REJECTED = 'kyc.rejected';
}
