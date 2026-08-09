<?php

declare(strict_types=1);

namespace App\Application\Compliance\DTO\Request;

use App\Domain\Compliance\Entity\ComplianceDocument;

class DocuSealInfo
{
    private function __construct(
        public ?\DateTimeImmutable $docuSealSignedAt,
        public ?string $docuSealDocumentUrl,
        public ?string $docuSealAuditLogUrl,
        public ?string $docuSealRejectedReason,
    ) {
    }

    public static function fromEntity(ComplianceDocument $complianceDocument): self
    {
        return new self(
            docuSealSignedAt: $complianceDocument->docuSealSignedAt,
            docuSealDocumentUrl: $complianceDocument->docuSealDocumentUrl,
            docuSealAuditLogUrl: $complianceDocument->docuSealAuditLogUrl,
            docuSealRejectedReason: $complianceDocument->docuSealRejectedReason,
        );
    }
}
