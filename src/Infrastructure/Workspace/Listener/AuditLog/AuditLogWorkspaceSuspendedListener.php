<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Listener\AuditLog;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Workspace\Event\WorkspaceSuspendedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class AuditLogWorkspaceSuspendedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(WorkspaceSuspendedEvent $event): void
    {
        Assert::notNull($event->fullName);
        Assert::notNull($event->email);

        $workspace = $event->workspace;

        $audit = AuditLog::initiate(
            eventName: AuditEventType::WORKSPACE_SUSPENDED,
            payload: [
                'suspension_reason' => $workspace->suspensionReason,
                'suspended_at' => $workspace->suspendedAt,
                'suspended_by_email' => $event->email,
                'actor_name' => $event->fullName,
                'actor_email' => $event->email,
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
