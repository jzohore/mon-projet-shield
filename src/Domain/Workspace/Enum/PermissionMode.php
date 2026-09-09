<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Enum;

/**
 * Posture d'un cabinet pour une action réglementée (validation / rejet d'un
 * dossier ou d'un rapport). Le champ est stocké dès maintenant ; l'application
 * effective du mode « soumission » est câblée dans un second temps.
 */
enum PermissionMode: string
{
    /** Le collaborateur agit directement. */
    case DELEGATED = 'delegated';
    /** Le collaborateur soumet, un administrateur valide (maker-checker). */
    case SUBMISSION = 'submission';
    /** Réservé aux administrateurs. */
    case RESERVED = 'reserved';

    public function getLabel(): string
    {
        return match ($this) {
            self::DELEGATED => 'Le collaborateur valide directement',
            self::SUBMISSION => 'Le collaborateur soumet pour validation',
            self::RESERVED => 'Réservé aux administrateurs',
        };
    }
}
