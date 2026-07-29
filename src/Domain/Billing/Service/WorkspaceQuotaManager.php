<?php

declare(strict_types=1);

namespace App\Domain\Billing\Service;

use App\Domain\Billing\Entity\Subscription;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use App\Domain\Screening\Repository\ScreeningAuditRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;

readonly class WorkspaceQuotaManager
{
    public function __construct(
        private ScreeningAuditRepositoryInterface $screeningAuditRepository,
        private KycFolderRepositoryInterface $kycFolderRepository,
    ) {
    }

    /**
     * @return int
     *             Renvoie le nombre de vérifications effectuées pendant le mois de facturation en cours
     */
    public function getSearchesCountThisMonth(Workspace $workspace): int
    {
        $subscription = $workspace->subscription;

        // FIX 1: If you want to return 0 when there is no subscription,
        // remove the Assert::notNull and keep the check.
        // If a subscription is MANDATORY for this logic, keep the Assert and remove the null check.
        if (!$subscription instanceof Subscription || !$subscription->isValid()) {
            return 0;
        }

        $periodStart = $subscription->currentPeriodStart;

        // FIX 2: Only keep this check if currentPeriodStart is nullable in your Entity.
        // If your Entity says "public DateTimeImmutable $currentPeriodStart",
        // then this if block is dead code and should be removed.

        $kycCount = $this->kycFolderRepository->countSearchesSince($workspace, $periodStart);
        $screeningCount = $this->screeningAuditRepository->countSearchesSince($workspace, $periodStart);

        return $kycCount + $screeningCount;
    }

    /**
     * @return bool
     *              Autorise ou bloque une nouvelle recherche
     */
    public function canPerformNewSearch(Workspace $workspace): bool
    {
        // On récupère le nombre consommé
        $count = $this->getSearchesCountThisMonth($workspace);

        // On vérifie face à ta constante (500)
        return $count < Subscription::PLAN_MAX_SEARCHES_PER_MONTH;
    }
}
