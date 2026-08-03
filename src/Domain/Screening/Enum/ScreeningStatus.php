<?php

declare(strict_types=1);

namespace App\Domain\Screening\Enum;

enum ScreeningStatus: string
{
    case WAIT = 'wait';
    case PENDING = 'pending';
    case GENERATED = 'generated';
    case FAILED = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::WAIT => 'En attente',
            self::PENDING => 'En cours',
            self::GENERATED => 'Généré',
            self::FAILED => 'Échoué',
        };
    }
}
