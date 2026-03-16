<?php

namespace App\Domain\User\Enum;

enum OnboardingStatus: string
{
    case PENDING = 'pending';           // Vient de cliquer sur le lien magique
    case WORKSPACE_SETUP = 'workspace'; // Est en train de créer son entreprise
    case COMPLETED = 'completed';       // A terminé, accès total
}
