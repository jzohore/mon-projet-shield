<?php

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
}
