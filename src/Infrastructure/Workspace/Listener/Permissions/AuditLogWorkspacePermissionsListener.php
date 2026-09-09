<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Listener\Permissions;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Workspace\Event\WorkspacePermissionsUpdatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class AuditLogWorkspacePermissionsListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(WorkspacePermissionsUpdatedEvent $event): void
    {
        $workspace = $event->workspace;
        $actor = $event->updatedBy;

        Assert::notNull($actor->id);
        Assert::notNull($actor->email);
        Assert::notNull($workspace->name);

        $this->auditLogRepository->save(AuditLog::initiate(
            eventName: AuditEventType::WORKSPACE_PERMISSIONS_UPDATED,
            payload: [
                'workspace_name' => $workspace->name,
                'actor_name' => $actor->getFullName(),
                'actor_email' => $actor->email,
                'permissions' => $event->snapshot,
            ],
            workspace: $workspace,
        ));
    }
}
