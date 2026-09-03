<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Compliance\Event\MeetingReportValidatedEvent;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class AuditLogMeetingReportValidatedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
        private ComplianceFolderRepositoryInterface $complianceFolderRepository,
    ) {
    }

    public function __invoke(MeetingReportValidatedEvent $event): void
    {
        $folder = $this->complianceFolderRepository->findOneBySlugId($event->folderSlugId);
        Assert::notNull($folder);

        $workspace = $folder->workspace;

        $audit = AuditLog::initiate(
            eventName: AuditEventType::MEETING_REPORT_VALIDATED,
            payload: [
                'workspace_name' => $workspace->name,
                'folder_reference' => $folder->reference,
                'report_slug_id' => $event->reportSlugId,
                'report_version' => $event->version,
                'text_adjusted_by_cgp' => $event->adjusted,
                'actor_name' => $event->validatedByName,
                'actor_email' => $event->validatedByEmail,
                'generated_at' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
