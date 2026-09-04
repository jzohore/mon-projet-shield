<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Event\DerRejectedEvent;
use App\Domain\Compliance\Exception\Document\DocumentNotFoundException;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class MarkDerAsRejectedUseCase
{
    public function __construct(
        private ComplianceDocumentRepositoryInterface $complianceDocumentRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(string $submissionId, \DateTimeImmutable $declinedAt, ?string $declineReason): void
    {
        $document = $this->complianceDocumentRepository->findBySubmissionId($submissionId);

        if (!$document instanceof ComplianceDocument) {
            throw DocumentNotFoundException::withId($submissionId);
        }

        // 🛡️ Idempotence : un rejeu du webhook `form.declined` ne rejoue ni
        // l'historique du dossier ni l'événement.
        if ($document->isDocuSealDeclined()) {
            return;
        }

        $document->markDerDeclined($declineReason, $declinedAt);
        $document->folder->markAsDerRejected($declinedAt->format('d/m/y H:i'), $declineReason);

        $this->complianceDocumentRepository->save($document);
        $this->eventDispatcher->dispatch(new DerRejectedEvent($submissionId, $declineReason, $declinedAt));
    }
}
