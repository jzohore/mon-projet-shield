<?php

namespace App\Infrastructure\Workspace\Listener;

use App\Application\Audit\DTO\Request\CreateAuditLogRequest;
use App\Application\Audit\UseCase\CreateAuditLogUseCase;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Event\WorkspaceCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

final readonly class WorkspaceFlowListener
{
    public function __construct(
        private CreateAuditLogUseCase $auditLogUseCase,
    ) {}
    #[AsEventListener]
    public function auditLog(WorkspaceCreatedEvent $event): void
    {
        $user = $event->user;
        Assert::isInstanceOf($user, User::class);
        $workspace = $event->workspace;
        Assert::isInstanceOf($workspace, Workspace::class);
        $auditLog = new CreateAuditLogRequest(
            eventName: AuditEventType::WORKSPACE_CREATED,
            resourceId: $workspace->slugId,
            data: [
                'workspace_name'   => $workspace->name,
                'created_by_email' => $user->email,
            ]
        );

        ($this->auditLogUseCase)($auditLog);
    }
}
