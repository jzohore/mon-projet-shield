<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Listener\Register;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\User\Event\UserRegisteredEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
readonly class AuditLogUserCreatedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(UserRegisteredEvent $event): void
    {
        $audit = AuditLog::initiate(
            eventName: AuditEventType::USER_REGISTERED,
            payload: [
                'target_user_id' => $event->userId,
                'actor_name' => $event->fullName,
                'actor_email' => $event->email,
            ],
        );

        $this->auditLogRepository->save($audit);
    }
}
