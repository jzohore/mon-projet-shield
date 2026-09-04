<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Event\DerSignedEvent;
use App\Domain\Compliance\Exception\Document\DocumentNotFoundException;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class MarkDerAsSignedUseCase
{
    public function __construct(
        private ComplianceDocumentRepositoryInterface $complianceDocumentRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(string $submissionId, string $documentUrl, string $auditLogUrl, \DateTimeImmutable $completedAt): void
    {
        $document = $this->complianceDocumentRepository->findBySubmissionId($submissionId);

        if (!$document instanceof ComplianceDocument) {
            throw DocumentNotFoundException::withId($submissionId);
        }

        // 🛡️ Idempotence : DocuSeal rejoue ses webhooks. Un second passage ne doit
        // ni réécrire la preuve, ni re-notifier le client, ni dupliquer l'AuditLog.
        if ($document->isDocuSealSigned()) {
            return;
        }

        $document->markAsDocuSealSigned($documentUrl, $auditLogUrl, $completedAt);
        $document->folder->markAsDerApproved($completedAt->format('d/m/y H:i'));
        $document->folder->markAsAwaitingClient($completedAt->format('d/m/y H:i'));
        $this->complianceDocumentRepository->save($document);

        $this->eventDispatcher->dispatch(new DerSignedEvent(
            submissionId: $submissionId,
            documentUrl: $documentUrl,
            auditLogUrl: $auditLogUrl,
            completedAt: $completedAt,
        ));
    }
}
