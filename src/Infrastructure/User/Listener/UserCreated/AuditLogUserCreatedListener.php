<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Listener\UserCreated;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\User\Event\UserCreatedEvent;
use Exception;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener(event: UserCreatedEvent::class)]
readonly class AuditLogUserCreatedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(UserCreatedEvent $event): void
    {
        $user = $event->user;
        Assert::notNull($user->id);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::USER_REGISTERED,
            payload: [
                'target_user_id' => $user->slugId,
                'email'          => $user->email,
                'full_name'      => $user->getFullName(),
            ],
            actor: $user->id->toString(),
        );

        $this->auditLogRepository->save($audit);
    }
}
