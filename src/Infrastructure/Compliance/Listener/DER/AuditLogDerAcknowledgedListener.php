<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener\DER;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Compliance\Event\DerAcknowledgedEvent;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

/**
 * Trace inaltérable de l'accusé de réception du DER. L'acteur est le client
 * identifié (jamais « le système »). Aucune donnée personnelle effaçable (IP,
 * user-agent) ici : le journal d'audit ne se purge pas — ces éléments vivent
 * sur la {@see \App\Domain\Compliance\Entity\DerAcknowledgement}.
 */
#[AsEventListener]
readonly class AuditLogDerAcknowledgedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
        private ComplianceDocumentRepositoryInterface $complianceDocumentRepository,
    ) {
    }

    public function __invoke(DerAcknowledgedEvent $event): void
    {
        $document = $this->complianceDocumentRepository->findById($event->getDocumentId());
        Assert::notNull($document, 'Le DER lié à cet accusé de réception est introuvable.');

        $folder = $document->folder;
        $workspace = $folder->workspace;
        Assert::notNull($workspace);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::DER_ACKNOWLEDGED,
            payload: [
                'document_id' => $event->getDocumentId(),
                'document_reference' => $folder->reference ?? 'N/A',
                'folder_slug_id' => $folder->slugId,
                'workspace_name' => $workspace->name,
                'actor_type' => 'client',
                'actor_name' => $event->getDeclaredName(),
                'acknowledgement_slug_id' => $event->getAcknowledgementSlugId(),
                'pdf_sha256' => $event->getPdfSha256(),
                'acknowledged_at' => $event->getAcknowledgedAt()->format(\DateTimeInterface::ATOM),
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
