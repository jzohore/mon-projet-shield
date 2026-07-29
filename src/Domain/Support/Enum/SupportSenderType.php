<?php

declare(strict_types=1);

namespace App\Domain\Support\Enum;

enum SupportSenderType: string
{
    case CLIENT = 'client';
    case ADMIN = 'admin';

    /**
     * Permet de savoir instantanément si c'est un message de l'équipe Kysure.
     */
    public function isAdmin(): bool
    {
        return self::ADMIN === $this;
    }

    /**
     * Utilisé pour la configuration de l'UI (ex: alignement des bulles).
     */
    public function getAlignmentClass(): string
    {
        return match ($this) {
            self::CLIENT => 'ml-auto justify-end', // Aligné à droite
            self::ADMIN => 'mr-auto justify-start', // Aligné à gauche
        };
    }

    /**
     * Couleurs des bulles de chat.
     */
    public function getBubbleClasses(): string
    {
        return match ($this) {
            self::CLIENT => 'bg-violet-600 text-white rounded-tr-none',
            self::ADMIN => 'bg-white border border-slate-200 text-slate-700 rounded-tl-none',
        };
    }
}
