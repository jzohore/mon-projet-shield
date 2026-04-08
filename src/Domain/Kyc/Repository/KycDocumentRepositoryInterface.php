<?php

namespace App\Domain\Kyc\Repository;

use App\Domain\Kyc\Entity\KycDocument;
use App\Domain\Kyc\Entity\KycFolder;
use App\Domain\Kyc\Entity\Stakeholder;
use App\Domain\Kyc\Enum\DocumentType;

interface KycDocumentRepositoryInterface
{
    public function save(KycDocument $kycDocument): void;
    public function findBySlugId(string $slugId): ?KycDocument;

    /**
     * @return array<int, KycDocument>
     */
    public function findPendingDocuments(): array;

    public function findOneByContext(KycFolder $folder, DocumentType $type, ?Stakeholder $stakeholder = null): ?KycDocument;
}
