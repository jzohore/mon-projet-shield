<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

/**
 * Sous-ensemble des actions de statut applicables à un compte de l'équipe KYSURE
 * (entrée du ChangeAdminAccountStatusUseCase). Distinct de AdminAccountAction,
 * qui couvre tout le cycle de vie.
 */
enum AdminStatusChange
{
    case SUSPEND;
    case REACTIVATE;
    case ARCHIVE;

    public function toAccountAction(): AdminAccountAction
    {
        return match ($this) {
            self::SUSPEND => AdminAccountAction::SUSPENDED,
            self::REACTIVATE => AdminAccountAction::REACTIVATED,
            self::ARCHIVE => AdminAccountAction::ARCHIVED,
        };
    }
}
