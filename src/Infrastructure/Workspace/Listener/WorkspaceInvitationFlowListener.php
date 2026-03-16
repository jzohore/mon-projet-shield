<?php

namespace App\Infrastructure\Workspace\Listener;

use App\Application\Audit\DTO\Request\CreateAuditLogRequest;
use App\Application\Audit\UseCase\CreateAuditLogUseCase;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Event\WorkspaceInvitationCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

final readonly class WorkspaceInvitationFlowListener
{
    public function __construct(
        private CreateAuditLogUseCase $auditLogUseCase,
    ) {}
    #[AsEventListener]
    public function auditLog(WorkspaceInvitationCreatedEvent $event): void
    {
        $invitation = $event->workspaceInvitation;
        $user = $invitation->owner;
        Assert::isInstanceOf($user, User::class);
        $workspace = $invitation->workspace;
        Assert::isInstanceOf($workspace, Workspace::class);
        $auditLog = new CreateAuditLogRequest(
            eventName: AuditEventType::WORKSPACE_MEMBER_ADDED,
            resourceId: $invitation->slugId,
            data: [
                'workspace_name'   => $workspace->name,
                'created_by_email' => $user->email,
            ]
        );

        ($this->auditLogUseCase)($auditLog);
    }
}
