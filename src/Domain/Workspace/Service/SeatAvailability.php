<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Service;

use App\Domain\Billing\Entity\Subscription;
use App\Domain\Billing\Enum\Plan;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;

/**
 * Sièges d'un cabinet : un siège = un membre actif OU une invitation en attente.
 *
 * - Cabinet abonné      → sièges = quantité facturée sur Stripe (Subscription::seatsCount).
 * - Cabinet en essai    → 2 sièges (le minimum d'un cabinet), le temps de tester.
 * - Compte indépendant  → 1 siège (pas de collaborateurs).
 */
readonly class SeatAvailability
{
    /** Sièges accordés à un cabinet en essai gratuit (avant abonnement). */
    public const int TRIAL_CABINET_SEATS = 2;

    public function __construct(
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
    ) {
    }

    public function allowedSeats(Workspace $workspace): int
    {
        if (!$workspace->isFirm()) {
            return 1;
        }

        $subscription = $workspace->subscription;
        if ($subscription instanceof Subscription && $subscription->isValid()) {
            return max(Plan::CABINET->getMinSeats(), $subscription->seatsCount);
        }

        return self::TRIAL_CABINET_SEATS;
    }

    public function usedSeats(Workspace $workspace): int
    {
        return $this->workspaceMemberRepository->countByWorkspace($workspace)
            + $this->workspaceInvitationRepository->countPendingByWorkspace($workspace);
    }

    public function remainingSeats(Workspace $workspace): int
    {
        return max(0, $this->allowedSeats($workspace) - $this->usedSeats($workspace));
    }

    public function hasFreeSeat(Workspace $workspace): bool
    {
        return $this->usedSeats($workspace) < $this->allowedSeats($workspace);
    }
}
