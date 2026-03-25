<?php

namespace App\Domain\Kyc\Repository;

use App\Domain\Kyc\Entity\Stakeholder;

interface StakeholderRepositoryInterface
{
    public function save(Stakeholder $stakeholder): void;
    public function remove(Stakeholder $stakeholder): void;
    public function findBySlugId(string $slugId): ?Stakeholder;
}
