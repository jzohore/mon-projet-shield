<?php

namespace App\Infrastructure\Workspace\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Workspace\Event\WorkspaceCreatedEvent;
use Exception;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class AuditLogWorkspaceCreatedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(WorkspaceCreatedEvent $event): void
    {
        $user = $event->user;
        Assert::notNull($user->id);
        $workspace = $event->workspace;

        $audit = AuditLog::initiate(
            eventName: AuditEventType::WORKSPACE_CREATED,
            payload: [
                'workspace_name'   => $workspace->name,
                'created_by_email' => $user->email,
            ],
            actor: $user->id->toString(),
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
