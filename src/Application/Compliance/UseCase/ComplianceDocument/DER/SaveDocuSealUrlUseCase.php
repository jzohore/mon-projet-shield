<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;

readonly class SaveDocuSealUrlUseCase
{
    public function __construct(
        private ComplianceDocumentRepositoryInterface $documentRepository,
    ) {
    }

    public function __invoke(ComplianceDocument $document, string $docuSealUrl, int $submissionId): void
    {
        $document->setDocuSealSignatureUrl($docuSealUrl);
        $document->setDocuSealSubmissionId($submissionId);
        $this->documentRepository->save($document);
    }
}
