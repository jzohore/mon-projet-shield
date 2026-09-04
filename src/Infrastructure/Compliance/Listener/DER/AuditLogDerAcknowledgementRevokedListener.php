<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener\DER;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Compliance\Event\DerAcknowledgementRevokedEvent;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class AuditLogDerAcknowledgementRevokedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
        private ComplianceDocumentRepositoryInterface $complianceDocumentRepository,
    ) {
    }

    public function __invoke(DerAcknowledgementRevokedEvent $event): void
    {
        $document = $this->complianceDocumentRepository->findById($event->getDocumentId());
        Assert::notNull($document, 'DER introuvable pour la révocation de l\'accusé de réception.');

        $folder = $document->folder;
        $workspace = $folder->workspace;
        Assert::notNull($workspace);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::DER_ACKNOWLEDGEMENT_REVOKED,
            payload: [
                'document_id' => $event->getDocumentId(),
                'document_reference' => $folder->reference ?? 'N/A',
                'folder_slug_id' => $folder->slugId,
                'workspace_name' => $workspace->name,
                'actor_type' => 'workspace_admin',
                'actor_name' => $event->getRevokedByName(),
                'acknowledgement_slug_id' => $event->getAcknowledgementSlugId(),
                'reason' => $event->getReason(),
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
