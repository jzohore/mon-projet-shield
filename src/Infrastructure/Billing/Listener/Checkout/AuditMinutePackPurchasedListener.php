<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Listener\Checkout;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Billing\Event\MinutePackPurchasedEvent;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: MinutePackPurchasedEvent::class)]
readonly class AuditMinutePackPurchasedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
        private WorkspaceRepositoryInterface $workspaceRepository,
    ) {
    }

    public function __invoke(MinutePackPurchasedEvent $event): void
    {
        $workspace = $this->workspaceRepository->findOneBySlug($event->workspaceSlugId);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::WORKSPACE_QUOTA_GRANTED,
            payload: [
                'workspace_slug_id' => $event->workspaceSlugId,
                'source' => 'stripe_purchase',
                'dossiers_granted' => 0,
                'minutes_granted' => $event->minutesGranted,
                'remaining_minutes' => $event->remainingMeetingMinutes,
                'invoice_url' => $event->invoiceUrl,
                'at' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
            ],
            workspace: $workspace instanceof Workspace ? $workspace : null,
        );

        $this->auditLogRepository->save($audit);
    }
}
