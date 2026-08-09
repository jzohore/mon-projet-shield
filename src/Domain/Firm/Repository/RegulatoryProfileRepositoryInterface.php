<?php

declare(strict_types=1);

namespace App\Domain\Firm\Repository;

use App\Domain\Firm\Entity\RegulatoryProfile;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Component\Uid\Uuid;

interface RegulatoryProfileRepositoryInterface
{
    public function save(RegulatoryProfile $regulatoryProfile, bool $isFlush = true): void;

    public function findById(Uuid|string $id): ?RegulatoryProfile;

    public function findOneByWorkspace(Workspace $workspace): ?RegulatoryProfile;
}
