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

        $actor = $event->revokedBy;

        Assert::notNull($actor->id);
        Assert::notNull($actor->email);

        $workspace = $event->workspace;
        Assert::notNull($workspace->name);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::WORKSPACE_INVITATION_REVOKED,
            payload: [
                'workspace_name' => $workspace->name,
                'invitation_slug_id' => $invitation->slugId,
                'revoked_by_name' => $actor->getFullName(),
                'revoked_by_email' => $actor->email,
                'invited_by_email' => $invitation->owner->email,
                'email_revoked' => $invitation->email,
                'role' => $invitation->invitedRole->getLabel(),
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
