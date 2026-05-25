<?php

namespace App\Infrastructure\Workspace\Listener\Member;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Workspace\Event\WorkspaceMemberRevokedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener(event: WorkspaceMemberRevokedEvent::class)]
readonly class AuditLogWorkspaceMemberRevokedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {}

    public function __invoke(WorkspaceMemberRevokedEvent $event): void
    {
        $revokedUser = $event->revokedUser;
        $actor = $event->actor;
        $workspace = $event->workspace;

        Assert::notNull($revokedUser->id);
        Assert::notNull($actor->id);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::WORKSPACE_MEMBER_REVOKED,
            payload: [
                'target_user_id' => $revokedUser->slugId,
                'target_email'   => $revokedUser->email,
                'workspace_name' => $workspace->name,
            ],
            actor: $actor->id->toString(),
        );

        $this->auditLogRepository->save($audit);
    }
}
