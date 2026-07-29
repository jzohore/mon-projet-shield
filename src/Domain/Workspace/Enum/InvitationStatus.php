<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Enum;

enum InvitationStatus: string
{
    case PENDING = 'pending';   // En attente
    case ACCEPTED = 'accepted'; // Acceptée
    case EXPIRED = 'expired';   // Expirée
    case REVOKED = 'revoked';   // Révoquée (annulée par l'admin)

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::ACCEPTED => 'Acceptée',
            self::EXPIRED => 'Expirée',
            self::REVOKED => 'Annulée',
        };
    }

    public function getColorClasses(): string
    {
        return match ($this) {
            self::PENDING => 'bg-amber-50 text-amber-700 border-amber-200',
            self::ACCEPTED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::EXPIRED => 'bg-slate-50 text-slate-500 border-slate-200',
            self::REVOKED => 'bg-rose-50 text-rose-700 border-rose-200',
        };
    }
}
