<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Listener\Invitation;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Workspace\Event\WorkspaceInvitationRevokeEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class AuditLogRevokeInvitationListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(WorkspaceInvitationRevokeEvent $event): void
    {
        $invitation = $event->workspaceInvitation;

        $user = $event->user;

        Assert::notNull($user->id);
        Assert::notNull($user->email);

        $workspace = $event->workspace;
        Assert::notNull($workspace->name);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::WORKSPACE_MEMBER_REVOKED,
            payload: [
                'workspace_name' => $workspace->name,
                'revoked_by_email' => $user->email,
                'email_revoked' => $invitation->email,
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
