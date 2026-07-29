<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Repository;

use App\Domain\Workspace\Entity\LegalUpdateDemand;

interface LegalUpdateDemandRepositoryInterface
{
    public function save(LegalUpdateDemand $legalUpdateDemand): void;
}
