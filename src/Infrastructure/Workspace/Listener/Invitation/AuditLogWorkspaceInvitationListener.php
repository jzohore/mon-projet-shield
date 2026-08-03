<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Listener\Invitation;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Workspace\Event\WorkspaceInvitationCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class AuditLogWorkspaceInvitationListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(WorkspaceInvitationCreatedEvent $event): void
    {
        $invitation = $event->workspaceInvitation;

        $user = $event->user;

        Assert::notNull($user->id);
        Assert::notNull($user->email);

        $workspace = $event->workspace;
        Assert::notNull($workspace->name);
        $audit = AuditLog::initiate(
            eventName: AuditEventType::WORKSPACE_MEMBER_ADDED,
            payload: [
                'workspace_name' => $workspace->name,
                'actor_name' => $user->getFullName(),
                'actor_email' => $user->email,
                'email_created' => $invitation->email,
                'role' => $invitation->invitedRole->getLabel(),
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
