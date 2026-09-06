<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Compliance\Event\ClientRelationshipEndedEvent;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Log d'audit « chapeau » de la clôture d'une relation client. Le détail par
 * dossier est tracé séparément ({@see AuditLogBusinessRelationshipEndedListener}).
 * Le motif est stocké **codé** (enum), jamais en texte libre — art. L.561-18 CMF.
 */
#[AsEventListener]
readonly class AuditLogClientRelationshipEndedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
        private WorkspaceRepositoryInterface $workspaceRepository,
    ) {
    }

    public function __invoke(ClientRelationshipEndedEvent $event): void
    {
        $workspace = $this->workspaceRepository->findOneBySlug($event->workspaceSlugId);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::CLIENT_RELATIONSHIP_ENDED,
            payload: [
                'client_slug_id' => $event->clientSlugId,
                'workspace_name' => $workspace instanceof Workspace ? $workspace->name : 'N/A',
                'actor_type' => 'workspace_admin',
                'actor_name' => $event->actorName,
                'actor_slug_id' => $event->actorSlugId,
                'reason_code' => $event->reason->value,
                'ended_folder_slug_ids' => $event->endedFolderSlugIds,
                'ended_folder_count' => count($event->endedFolderSlugIds),
                'deleted_draft_slug_ids' => $event->deletedDraftSlugIds,
                'latest_purge_due_at' => $event->latestPurgeDueAt?->format(\DateTimeInterface::ATOM),
                'at' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
