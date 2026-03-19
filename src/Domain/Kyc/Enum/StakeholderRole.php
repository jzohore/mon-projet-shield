<?php

namespace App\Domain\Kyc\Enum;

enum StakeholderRole: string
{
    case DIRECTOR = 'director';
    case BENEFICIAL_OWNER = 'beneficial_owner';
    case LEGAL_REPRESENTATIVE = 'legal_rep';

    public function getLabel(): string
    {
        return match ($this) {
            self::DIRECTOR => 'Dirigeant / Mandataire social',
            self::BENEFICIAL_OWNER => 'Bénéficiaire Effectif (>25%)',
            self::LEGAL_REPRESENTATIVE => 'Représentant Légal',
        };
    }
}
