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
 * - Abonnement « cabinet » actif → sièges = quantité facturée (Subscription::seatsCount).
 * - Abonnement « indépendant » actif → 1 siège.
 * - Sans abonnement : cabinet en essai → 2 sièges ; indépendant → 1.
 *
 * Le plafond suit le PLAN de l'abonnement, pas le type du workspace : un compte
 * peut avoir souscrit l'offre cabinet sans que son `type` ait été recalé.
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
        $subscription = $workspace->subscription;

        if ($subscription instanceof Subscription && $subscription->isValid()) {
            return $this->isCabinetPlan($subscription->planReference)
                ? max(Plan::CABINET->getMinSeats(), $subscription->seatsCount)
                : 1;
        }

        return $workspace->isFirm() ? self::TRIAL_CABINET_SEATS : 1;
    }

    private function isCabinetPlan(string $planReference): bool
    {
        // 'cabinet' (nouveau modèle) ou 'kysure_cabinet_300' (essai historique).
        return str_contains($planReference, 'cabinet');
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
