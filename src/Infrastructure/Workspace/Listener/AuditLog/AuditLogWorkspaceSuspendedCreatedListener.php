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
readonly class AuditLogWorkspaceSuspendedCreatedListener
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
        $workspace = $event->workspace;
        $user = $event->user;

        Assert::notNull($user->id);
        Assert::notNull($workspace->id);
        Assert::notNull($workspace->suspendedAt);
        Assert::notNull($workspace->suspensionReason);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::WORKSPACE_CREATED,
            payload: [
                'workspace_suspended_name' => $workspace->name,
                'suspension_reason' => $workspace->suspensionReason,
                'suspended_at' => $workspace->suspendedAt->format('Y-m-d H:i:s'),
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
