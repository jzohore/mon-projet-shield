<?php

declare(strict_types=1);

namespace App\Domain\Kyc\Enum;

enum DocumentStatus: string
{
    case GENERATED = 'generated';
    case FAILED = 'failed';
    case PROCESSING = 'processing';
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
            self::PROCESSING => 'Traitement en cours',
            self::GENERATED => 'Généré',
            self::FAILED => 'Échoué',
        };
    }

    /**
     * Retourne les statuts qui nécessitent une action de la part du client final.
     *
     * @return array<self>
     */
    public static function getActionableByClientStatuses(): array
    {
        return [
            self::PENDING,
            self::REJECTED,
        ];
    }
}
