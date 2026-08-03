<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Enum;

enum WorkspaceType: string
{
    case INDIVIDUAL = 'individual';
    case FIRM = 'firm';

    public function getLabel(): string
    {
        return match ($this) {
            self::INDIVIDUAL => 'Indépendant',
            self::FIRM => 'Cabinet / Équipe',
        };
    }
}
