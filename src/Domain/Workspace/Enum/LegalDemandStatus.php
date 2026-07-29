<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Enum;

enum LegalDemandStatus: string
{
    case CREATED = 'created';   // En attente
    case PENDING = 'pending';   // En attente
    case ACCEPTED = 'accepted'; // Acceptée
    case REJECTED = 'rejected';   // Expirée

    public function getLabel(): string
    {
        return match ($this) {
            self::CREATED => 'En création',
            self::PENDING => 'En attente',
            self::ACCEPTED => 'Acceptée',
            self::REJECTED => 'Rejetée',
        };
    }

    public function getColorClasses(): string
    {
        return match ($this) {
            self::CREATED => 'bg-violet-50 text-amber-700 border-amber-200',
            self::PENDING => 'bg-amber-50 text-amber-700 border-amber-200',
            self::ACCEPTED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::REJECTED => 'bg-slate-50 text-slate-500 border-slate-200',
        };
    }
}
