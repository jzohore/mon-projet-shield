<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Listener\Member;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Event\WorkspaceMemberReactivatedEvent;
use App\Domain\Workspace\Event\WorkspaceMemberSuspendedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

readonly class AuditLogWorkspaceMemberStatusListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    #[AsEventListener(event: WorkspaceMemberSuspendedEvent::class)]
    public function onSuspended(WorkspaceMemberSuspendedEvent $event): void
    {
        $this->record(AuditEventType::WORKSPACE_MEMBER_SUSPENDED, $event->targetUser, $event->actor, $event->workspace);
    }

    #[AsEventListener(event: WorkspaceMemberReactivatedEvent::class)]
    public function onReactivated(WorkspaceMemberReactivatedEvent $event): void
    {
        $this->record(AuditEventType::WORKSPACE_MEMBER_REACTIVATED, $event->targetUser, $event->actor, $event->workspace);
    }

    private function record(AuditEventType $type, User $target, User $actor, Workspace $workspace): void
    {
        Assert::notNull($target->id);
        Assert::notNull($actor->id);
        Assert::notNull($workspace->name);

        $this->auditLogRepository->save(AuditLog::initiate(
            eventName: $type,
            payload: [
                'workspace_name' => $workspace->name,
                'target_user_id' => $target->slugId,
                'target_email' => $target->email,
                'actor_name' => $actor->getFullName(),
                'actor_email' => $actor->email,
            ],
            workspace: $workspace,
        ));
    }
}
