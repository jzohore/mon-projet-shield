<?php

namespace App\Domain\Kyc\Repository;

use App\Domain\Kyc\Entity\KycDocument;

interface KycDocumentRepositoryInterface
{
    public function save(KycDocument $kycDocument): void;
    public function findBySlugId(string $slugId): ?KycDocument;
}
