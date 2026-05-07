<?php

namespace App\Application\Dashboard\UseCase;

use App\Application\Billing\Provider\MrrProviderInterface;
use App\Application\Dashboard\DTO\AdminDashboardStats;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Billing\Enum\SubscriptionStatus;
use App\Domain\Billing\Repository\SubscriptionRepositoryInterface;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use App\Domain\Screening\Repository\ScreeningAuditRepositoryInterface;
use App\Domain\Support\Repository\SupportThreadRepositoryInterface;
use App\Domain\Tracking\Repository\ClickLogRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;

final readonly class GetAdminDashboardStatsUseCase
{
    public function __construct(
        private SupportThreadRepositoryInterface $threadRepository,
        private WorkspaceRepositoryInterface $workspaceRepository,
        private ClickLogRepositoryInterface $clickLogRepository,
        private KycFolderRepositoryInterface $kycRepository,
        private ScreeningAuditRepositoryInterface $screeningRepository,
        private MrrProviderInterface $mrrProvider,
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {}

    public function __invoke(): AdminDashboardStats
    {
        // 1. Récupération des données via les Repositories (Simulation pour l'exemple)
        // $mrr = $this->subscriptionRepository->calculateMonthlyRecurringRevenue();
        $mrr = $this->mrrProvider->getCurrentMrr();

        // 2. Comptage des abonnements actifs via l'Enum
        // On demande à l'Enum "Quels sont les statuts qui définissent un client actif ?"
        $activeStatuses = [SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIALING];
        $activeWorkspaces = $this->subscriptionRepository->countByStatuses($activeStatuses);

        $totalWorkspaces = $this->workspaceRepository->countAll();
        //$activeWorkspaces = $this->workspaceRepository->countActive();

        // On récupère les tickets globaux en attente d'une réponse de l'équipe
        $openTickets = $this->threadRepository->countAllOpenTickets();

        $apiSuccessRate = 99.9;
        $totalKyc        = $this->kycRepository->countAll();
        $totalScreenings = $this->screeningRepository->countAll();
        $linkedinClicks = $this->clickLogRepository->countBySource('linkedin');

        // On récupère les stats des 30 derniers jours par exemple
        $thirtyDaysAgo = new \DateTimeImmutable('-30 days');
        $clickStats    = $this->clickLogRepository->getStatsByElement($thirtyDaysAgo);
        $latestWorkspaces = $this->workspaceRepository->findLatest();
        $latestAuditLogs = $this->auditLogRepository->findLatestLogs();

        // 2. Retourne le DTO formaté pour la vue
        return new AdminDashboardStats(
            $mrr,
            $activeWorkspaces,
            $totalWorkspaces,
            $openTickets,
            $apiSuccessRate,
            $totalKyc,
            $totalScreenings,
            $linkedinClicks,
            $clickStats,
            $latestWorkspaces,
            $latestAuditLogs
        );
    }
}
