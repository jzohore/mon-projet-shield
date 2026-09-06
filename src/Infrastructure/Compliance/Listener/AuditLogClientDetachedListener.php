<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Compliance\Event\ClientDetachedFromWorkspaceEvent;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
readonly class AuditLogClientDetachedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
        private WorkspaceRepositoryInterface $workspaceRepository,
    ) {
    }

    public function __invoke(ClientDetachedFromWorkspaceEvent $event): void
    {
        $workspace = $this->workspaceRepository->findOneBySlug($event->workspaceSlugId);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::CLIENT_DETACHED_FROM_WORKSPACE,
            payload: [
                'client_slug_id' => $event->clientSlugId,
                'workspace_name' => $workspace instanceof Workspace ? $workspace->name : 'N/A',
                'actor_type' => 'workspace_admin',
                'actor_name' => $event->actorName,
                'actor_slug_id' => $event->actorSlugId,
                'deleted_draft_slug_ids' => $event->deletedDraftSlugIds,
                'deleted_draft_count' => count($event->deletedDraftSlugIds),
                'at' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
