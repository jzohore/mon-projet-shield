<?php

declare(strict_types=1);

namespace App\Domain\Support\Enum;

enum SupportThreadStatus: string
{
    case OPEN = 'open';
    case RESOLVED = 'resolved';

    /**
     * Retourne le libellé lisible par l'utilisateur.
     * Idéal pour les tableaux de bord ou EasyAdmin.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::OPEN => 'En cours',
            self::RESOLVED => 'Résolu',
        };
    }

    /**
     * Retourne les classes Tailwind pour un beau badge.
     */
    public function getBadgeClasses(): string
    {
        return match ($this) {
            self::OPEN => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
            self::RESOLVED => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
        };
    }

    /**
     * Retourne le nom de l'icône (Symfony UX Icon / Lucide).
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::OPEN => 'lucide:clock-4',
            self::RESOLVED => 'lucide:check-circle-2',
        };
    }

    // --- HELPERS MÉTIER ---

    public function isOpen(): bool
    {
        return self::OPEN === $this;
    }

    public function isResolved(): bool
    {
        return self::RESOLVED === $this;
    }
}
