<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\User\Event\UserUpdateProfilEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class AuditLogUpdateProfilListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(UserUpdateProfilEvent $event): void
    {
        $user = $event->user;
        $workspace = $event->workspace;

        Assert::notNull($user->id);
        Assert::notNull($workspace->name);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::USER_PROFILE_UPDATED,
            payload: [
                'actor_name' => $user->getFullName(),
                'actor_email' => $user->email,
                'workspace_name' => $workspace->name,
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
