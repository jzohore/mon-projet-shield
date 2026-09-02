<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Compliance\Event\MeetingReportRevokedEvent;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class AuditLogMeetingReportRevokedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
        private ComplianceFolderRepositoryInterface $complianceFolderRepository,
    ) {
    }

    public function __invoke(MeetingReportRevokedEvent $event): void
    {
        $folder = $this->complianceFolderRepository->findOneBySlugId($event->folderSlugId);
        Assert::notNull($folder);

        $workspace = $folder->workspace;

        $audit = AuditLog::initiate(
            eventName: AuditEventType::MEETING_REPORT_REVOKED,
            payload: [
                'workspace_name' => $workspace->name,
                'folder_reference' => $folder->reference,
                'report_slug_id' => $event->reportSlugId,
                'report_version' => $event->version,
                'reason' => $event->reason,
                'actor_name' => $event->revokedByName,
                'actor_email' => $event->revokedByEmail,
                'generated_at' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
