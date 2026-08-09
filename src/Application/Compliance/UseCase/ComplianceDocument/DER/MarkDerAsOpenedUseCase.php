<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Domain\Compliance\Exception\Document\DocumentNotFoundException;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;

readonly class MarkDerAsOpenedUseCase
{
    public function __construct(
        private ComplianceDocumentRepositoryInterface $complianceDocumentRepository,
    ) {
    }

    public function __invoke(string $submissionId, \DateTimeImmutable $openedAt): void
    {
        $document = $this->complianceDocumentRepository->findBySubmissionId($submissionId);

        if (!$document instanceof \App\Domain\Compliance\Entity\ComplianceDocument) {
            throw DocumentNotFoundException::withId($submissionId);
        }

        $document->folder->markAsDerOpened($openedAt->format('d/m/y H:i'));
        $this->complianceDocumentRepository->save($document);
    }
}
