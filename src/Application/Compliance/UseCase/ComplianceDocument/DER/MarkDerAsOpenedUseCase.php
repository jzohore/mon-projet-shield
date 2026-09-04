<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Domain\Compliance\Entity\ComplianceDocument;
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

        if (!$document instanceof ComplianceDocument) {
            throw DocumentNotFoundException::withId($submissionId);
        }

        // 🛡️ Idempotence + anti-régression : DocuSeal émet un `form.viewed` à chaque
        // ouverture et ne garantit pas l'ordre de livraison. On n'enregistre que la
        // première consultation, et jamais après une signature ou un refus.
        if ($document->isDocuSealOpened() || $document->isDocuSealSigned() || $document->isDocuSealDeclined()) {
            return;
        }

        $document->markDerOpened($openedAt);
        $document->folder->markAsDerOpened($openedAt->format('d/m/y H:i'));
        $this->complianceDocumentRepository->save($document);
    }
}
