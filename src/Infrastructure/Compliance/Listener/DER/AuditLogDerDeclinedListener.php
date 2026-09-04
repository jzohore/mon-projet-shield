<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener\DER;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Compliance\Event\DerDeclinedEvent;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class AuditLogDerDeclinedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
        private ComplianceDocumentRepositoryInterface $complianceDocumentRepository,
    ) {
    }

    public function __invoke(DerDeclinedEvent $event): void
    {
        $document = $this->complianceDocumentRepository->findById($event->getDocumentId());
        Assert::notNull($document, 'DER introuvable pour le refus client.');

        $folder = $document->folder;
        $workspace = $folder->workspace;
        Assert::notNull($workspace);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::DER_DECLINED,
            payload: [
                'document_id' => $event->getDocumentId(),
                'document_reference' => $folder->reference ?? 'N/A',
                'folder_slug_id' => $folder->slugId,
                'workspace_name' => $workspace->name,
                'actor_type' => 'client',
                'reason' => $event->getReason(),
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
