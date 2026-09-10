<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

use App\Domain\AuditLog\Enum\AuditEventType;

/**
 * Actions du cycle de vie d'un compte de l'équipe KYSURE. Chaque action porte
 * son type d'audit et indique si le membre concerné doit être notifié par e-mail.
 */
enum AdminAccountAction: string
{
    case CREATED = 'created';
    case PROFILE_UPDATED = 'profile_updated';
    case ROLES_CHANGED = 'roles_changed';
    case SUSPENDED = 'suspended';
    case REACTIVATED = 'reactivated';
    case ARCHIVED = 'archived';
    case DELETED = 'deleted';

    public function auditType(): AuditEventType
    {
        return match ($this) {
            self::CREATED => AuditEventType::ADMIN_ACCOUNT_CREATED,
            self::PROFILE_UPDATED => AuditEventType::ADMIN_ACCOUNT_PROFILE_UPDATED,
            self::ROLES_CHANGED => AuditEventType::ADMIN_ACCOUNT_ROLES_CHANGED,
            self::SUSPENDED => AuditEventType::ADMIN_ACCOUNT_SUSPENDED,
            self::REACTIVATED => AuditEventType::ADMIN_ACCOUNT_REACTIVATED,
            self::ARCHIVED => AuditEventType::ADMIN_ACCOUNT_ARCHIVED,
            self::DELETED => AuditEventType::ADMIN_ACCOUNT_DELETED,
        };
    }

    /** Le membre concerné est-il notifié par e-mail de cette action ? */
    public function notifiesTarget(): bool
    {
        return match ($this) {
            self::PROFILE_UPDATED => false,
            default => true,
        };
    }
}
