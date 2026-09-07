<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Event\WorkspaceQuotaGrantedEvent;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
readonly class AuditLogWorkspaceQuotaGrantedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
        private WorkspaceRepositoryInterface $workspaceRepository,
    ) {
    }

    public function __invoke(WorkspaceQuotaGrantedEvent $event): void
    {
        $workspace = $this->workspaceRepository->findOneBySlug($event->workspaceSlugId);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::WORKSPACE_QUOTA_GRANTED,
            payload: [
                'workspace_slug_id' => $event->workspaceSlugId,
                'dossiers_granted' => $event->dossiersGranted,
                'minutes_granted' => $event->minutesGranted,
                'trial_dossiers_remaining' => $event->trialDossiersRemaining,
                'remaining_minutes' => $event->remainingMinutes,
                'reason' => $event->reason,
                'actor_type' => 'kysure_admin',
                'actor_name' => $event->actorName,
                'actor_slug_id' => $event->actorSlugId,
                'at' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
            ],
            workspace: $workspace instanceof Workspace ? $workspace : null,
        );

        $this->auditLogRepository->save($audit);
    }
}
