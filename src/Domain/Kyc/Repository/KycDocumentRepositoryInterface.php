<?php

declare(strict_types=1);

namespace App\Domain\Kyc\Repository;

use App\Domain\Compliance\Enum\DocumentType;
use App\Domain\Kyc\Entity\KycDocument;
use App\Domain\Kyc\Entity\KycFolder;
use App\Domain\Kyc\Entity\Stakeholder;

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
