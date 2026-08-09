<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

use App\Domain\Compliance\Enum\ComplianceFolderStatus;

enum ClientPortalStatus: string
{
    case UP_TO_DATE = 'up_to_date';
    case ACTION_REQUIRED = 'action_required';
    case UNDER_REVIEW = 'under_review';

    public static function fromFolderStatus(ComplianceFolderStatus $folderStatus): self
    {
        return match ($folderStatus) {
            // Le client doit uploader des pièces justificatives
            ComplianceFolderStatus::AWAITING_CLIENT,
            ComplianceFolderStatus::PENDING_DOCS,
            ComplianceFolderStatus::NEEDS_CORRECTION => self::ACTION_REQUIRED,

            // Le cabinet analyse le KYC
            // Le Graal de la conformité
            ComplianceFolderStatus::APPROVED => self::UP_TO_DATE,

            default => self::UNDER_REVIEW,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::UP_TO_DATE => 'Dossier complet et validé',
            self::ACTION_REQUIRED => 'Pièces justificatives requises',
            self::UNDER_REVIEW => 'En cours d\'analyse par le cabinet',
        };
    }

    public function getColorTheme(): string
    {
        return match ($this) {
            self::UP_TO_DATE => 'emerald',
            self::ACTION_REQUIRED => 'amber',
            self::UNDER_REVIEW => 'blue',
        };
    }
}
