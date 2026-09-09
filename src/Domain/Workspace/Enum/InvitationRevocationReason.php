<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Enum;

/**
 * Motif d'annulation d'une invitation collaborateur. Liste fermée : le journal
 * d'audit doit rester exploitable en contrôle (« pourquoi cet accès a-t-il été
 * retiré ? »).
 */
enum InvitationRevocationReason: string
{
    case DEPARTURE = 'departure';
    case DATA_ENTRY_ERROR = 'data_entry_error';
    case MISSION_ENDED = 'mission_ended';
    case SECURITY_INCIDENT = 'security_incident';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::DEPARTURE => 'Départ du collaborateur',
            self::DATA_ENTRY_ERROR => 'Erreur de saisie',
            self::MISSION_ENDED => 'Fin de mission',
            self::SECURITY_INCIDENT => 'Incident de sécurité',
            self::OTHER => 'Autre',
        };
    }
}
