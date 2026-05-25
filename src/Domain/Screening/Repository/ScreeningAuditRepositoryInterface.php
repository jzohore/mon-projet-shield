<?php

namespace App\Domain\Screening\Repository;

use App\Domain\Screening\Entity\ScreeningAudit;
use App\Domain\Workspace\Entity\Workspace;
use DateTimeImmutable;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Uid\Uuid;

interface ScreeningAuditRepositoryInterface
{
    /**
     * @param ScreeningAudit $audit
     * @return void
     */
    public function save(ScreeningAudit $audit): void;

    /**
     * @param Workspace $workspace
     * @param string $query
     * @param int $hoursLimit
     * @return ScreeningAudit|null
     */
    public function findRecentIdenticalSearch(Workspace $workspace, string $query, int $hoursLimit): ?ScreeningAudit;

    /**
     * @param string $id
     * @return ScreeningAudit|null
     */
    public function findOneBySlug(string $id): ?ScreeningAudit;

    /**
     * @param Uuid $id
     * @return ScreeningAudit
     */
    public function getById(Uuid $id): ScreeningAudit;

    /**
     * @return Pagerfanta<ScreeningAudit>
     */
    public function getScreeningList(string $workspaceSlugId, ?string $search = null): Pagerfanta;

    /**
     * @param Workspace $workspace
     * @param DateTimeImmutable $since
     * @return int
     */
    public function countSearchesSince(Workspace $workspace, DateTimeImmutable $since): int;

    public function countAll(): int;
}
