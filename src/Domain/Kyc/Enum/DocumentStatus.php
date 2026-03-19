<?php

namespace App\Domain\Kyc\Enum;

enum DocumentStatus: string
{
    case PENDING = 'pending';
    case UPLOADED = 'uploaded';
    case VALID = 'valid';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::UPLOADED => 'Document reçu',
            self::VALID => 'Document valide',
            self::REJECTED => 'Document refusé',
            self::EXPIRED => 'Document expiré',
        };
    }
}
