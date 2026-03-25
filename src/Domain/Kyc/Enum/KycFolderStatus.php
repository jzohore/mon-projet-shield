<?php

namespace App\Domain\Kyc\Enum;

enum KycFolderStatus: string
{
    case DRAFT = 'draft';
    case AWAITING_CLIENT = 'awaiting_client';
    case IN_REVIEW = 'in_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case ARCHIVED = 'archived';
    case NEEDS_CORRECTION = 'needs_correction';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::AWAITING_CLIENT => 'En attente du client',
            self::IN_REVIEW => 'En cours d\'analyse',
            self::APPROVED => 'Dossier validé',
            self::REJECTED => 'Dossier refusé',
            self::ARCHIVED => 'Dossier archivé',
            self::NEEDS_CORRECTION => 'Corrections requises',
        };
    }
}
