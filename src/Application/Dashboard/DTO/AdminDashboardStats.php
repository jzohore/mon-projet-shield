<?php

namespace App\Application\Dashboard\DTO;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\Workspace\Entity\Workspace;

final readonly class AdminDashboardStats
{
    public function __construct(
        public float $mrr,
        public int $activeWorkspaces,
        public int $totalWorkspaces,
        public int $openTickets,
        public float $apiSuccessRate,
        public int $totalKycCreated,
        public int $totalScreeningsDone,
        // 👇 Nouvelles métriques de conversion (ClickLog)
        public int $totalLinkedinClicks,
        /** @var array<string, int> */
        public array $clickStatsByElement,
        /** @var array<Workspace> */
        public array $latestWorkspaces = [],
        /** @var AuditLog[] */
        public array $latestAuditLogs = []
    ) {}
}
