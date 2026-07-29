<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Listener\AuditLog;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Workspace\Event\WorkspaceUpdatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class AuditLogWorkspaceUpdatedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(WorkspaceUpdatedEvent $event): void
    {
        $user = $event->user;
        Assert::notNull($user->id);
        $workspace = $event->workspace;

        $audit = AuditLog::initiate(
            eventName: AuditEventType::WORKSPACE_UPDATED,
            payload: [
                'workspace_new_name' => $workspace->name,
                'workspace_old_name' => $event->oldName,
                'workspace_old_siren' => $event->oldSiren,
                'updated_by_email' => $user->email,
                'actor_name' => $user->getFullName(),
                'actor_email' => $user->email,
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
