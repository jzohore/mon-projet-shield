<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Compliance\Event\DocumentOcrProcessedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

/**
 * Trace l'analyse automatique d'un document. L'acteur est explicitement le
 * système OCR (Textract) — jamais un humain : c'est un signalement, pas une
 * décision. Le motif du rejet éventuel, lui, sera tracé quand le CGP tranchera.
 */
#[AsEventListener]
readonly class AuditLogDocumentOcrProcessedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    public function __invoke(DocumentOcrProcessedEvent $event): void
    {
        $document = $event->document;
        $folder = $document->folder;
        $workspace = $folder->workspace;

        Assert::notNull($document->id);
        Assert::notNull($workspace);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::KYC_DOCUMENT_OCR_PROCESSED,
            payload: [
                'document_id' => $document->id->toString(),
                'document_type' => $document->type->value,
                'folder_slug_id' => $folder->slugId,
                'workspace_name' => $workspace->name,
                'actor_type' => 'system_ocr',
                'extraction_succeeded' => $event->extractionSucceeded,
                'findings_count' => count($event->findings),
                'validator_version' => $document->ocrValidatorVersion,
                'processed_at' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
