<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener\DER;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Compliance\Event\DerSignedEvent;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class AuditLogDerSignedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
        private ComplianceDocumentRepositoryInterface $complianceDocumentRepository,
    ) {
    }

    public function __invoke(DerSignedEvent $event): void
    {
        $document = $this->complianceDocumentRepository->findBySubmissionId($event->getSubmissionId());

        Assert::notNull($document, 'Le document lié à cette signature est introuvable.');

        $folder = $document->folder;
        $workspace = $folder->workspace;

        Assert::notNull($document->id);
        Assert::notNull($workspace);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::DER_SIGNED, // 🪄 Type d'événement corrigé
            payload: [
                'document_id' => $document->id->toString(),
                'document_reference' => $folder->reference ?? 'N/A',
                'folder_slug_id' => $folder->slugId,
                'workspace_name' => $workspace->name,
                'actor_type' => 'system',
                'actor_name' => 'DocuSeal Webhook',
                'docuseal_submission_id' => $event->getSubmissionId(),
                'signed_at' => $event->getCompletedAt()->format(\DateTimeInterface::ATOM),
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
