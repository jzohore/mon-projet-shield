<?php

namespace App\Domain\Screening\Repository;

use App\Domain\Screening\Entity\ScreeningAudit;
use App\Domain\Workspace\Entity\Workspace;
use Pagerfanta\Pagerfanta;

interface ScreeningAuditRepositoryInterface
{
    public function save(ScreeningAudit $audit): void;
    public function findRecentIdenticalSearch(Workspace $workspace, string $query, int $hoursLimit): ?ScreeningAudit;

    public function findOneBySlug(string $id): ?ScreeningAudit;

    /**
     * @return Pagerfanta<ScreeningAudit>
     */
    public function getScreeningList(string $workspaceSlugId, ?string $search = null): Pagerfanta;
}
