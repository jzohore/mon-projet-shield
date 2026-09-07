<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Event;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * L'utilisateur a choisi son type de compte (Indépendant / Cabinet) à l'étape
 * « plan » de l'onboarding. Le workspace est actif mais l'onboarding n'est PAS
 * terminé : la finalisation reste à confirmer.
 *
 * Sert à préparer en tâche de fond ce qui dépend du type de compte (client
 * Stripe, mode de facturation), pendant que l'utilisateur voit l'écran de
 * finalisation. À ne pas confondre avec {@see \App\Domain\User\Event\UserOnboardingCompletedEvent},
 * émis lui à la toute fin.
 */
final class WorkspacePlanSelectedEvent extends Event
{
    public function __construct(
        public readonly User $user,
        public readonly Workspace $workspace,
    ) {
    }
}
