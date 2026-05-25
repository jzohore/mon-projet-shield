<?php

namespace App\Application\Compliance\DTO\Response;

use App\Domain\Compliance\Entity\ComplianceFolder;

readonly class DraftFolderResponse
{
    private function __construct(
        public string $folderSLugId,
        public string $reference,
        public string $slugId,
    ) {}

    public static function fromEntity(ComplianceFolder $complianceFolder): DraftFolderResponse
    {
        return new self(
            folderSLugId: $complianceFolder->slugId,
            reference: $complianceFolder->reference,
            slugId: $complianceFolder->slugId,
        );
    }
}
