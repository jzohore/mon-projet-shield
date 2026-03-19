<?php

namespace App\Infrastructure\Workspace\Listener;

use App\Application\Audit\DTO\Request\CreateAuditLogRequest;
use App\Application\Audit\UseCase\CreateAuditLogUseCase;
use App\Application\Subscription\DTO\SubscriptionRequest;
use App\Application\Subscription\UseCase\SaveSubscriptionUseCase;
use App\Application\Workspace\DTO\Request\WorkspaceMemberRequest;
use App\Application\Workspace\UseCase\SaveWorkspaceMemberUseCase;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\Subscription\Enum\Plan;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Enum\InvitedRole;
use App\Domain\Workspace\Event\WorkspaceCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

final readonly class WorkspaceFlowListener
{
    public function __construct(
        private CreateAuditLogUseCase $auditLogUseCase,
        private SaveWorkspaceMemberUseCase $saveWorkspaceMemberUseCase,
        private SaveSubscriptionUseCase $saveSubscriptionUseCase,
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

    #[AsEventListener]
    public function saveWorkspaceMember(WorkspaceCreatedEvent $event): void
    {
        $user = $event->user;
        Assert::isInstanceOf($user, User::class);
        $workspace = $event->workspace;
        Assert::isInstanceOf($workspace, Workspace::class);

        $member = new WorkspaceMemberRequest();
        $member->workspaceSlugId = $workspace->slugId;
        $member->userSlugId = $user->slugId;
        $member->role = InvitedRole::ROLE_WORKSPACE_ADMIN;

        ($this->saveWorkspaceMemberUseCase)($member);
    }

    #[AsEventListener]
    public function saveSubscription(WorkspaceCreatedEvent $event): void
    {
        $workspace = $event->workspace;
        Assert::isInstanceOf($workspace, Workspace::class);

        $sub = new SubscriptionRequest();
        $sub->workspaceSlugId = $workspace->slugId;
        $sub->plan = Plan::TRIAL;
        $sub->expiresAt = new \DateTimeImmutable('+1 month');

        ($this->saveSubscriptionUseCase)($sub);
    }
}
