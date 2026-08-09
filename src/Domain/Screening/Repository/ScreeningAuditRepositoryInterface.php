<?php

declare(strict_types=1);

namespace App\Domain\Screening\Repository;

use App\Domain\Screening\Entity\ScreeningAudit;
use App\Domain\Workspace\Entity\Workspace;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Uid\Uuid;

interface ScreeningAuditRepositoryInterface
{
    public function save(ScreeningAudit $audit): void;

    public function findRecentIdenticalSearch(Workspace $workspace, string $query, int $hoursLimit): ?ScreeningAudit;

    public function findOneBySlug(string $id): ?ScreeningAudit;

    public function getById(Uuid|string $id): ScreeningAudit;

    /**
     * @return Pagerfanta<ScreeningAudit>
     */
    public function getScreeningList(string $workspaceSlugId, ?string $search = null): Pagerfanta;

    public function countSearchesSince(Workspace $workspace, \DateTimeImmutable $since): int;

    public function countAll(): int;
}
