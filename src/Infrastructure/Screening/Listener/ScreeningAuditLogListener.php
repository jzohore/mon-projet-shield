<?php

namespace App\Infrastructure\Screening\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Screening\Event\ScreeningCompletedEvent;
use Exception;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class ScreeningAuditLogListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(ScreeningCompletedEvent $event): void
    {
        $user = $event->user;
        Assert::notNull($user->id);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::USER_REGISTERED,
            payload: [
                'user_email' => $user->email,
                'query_searched' => $event->screeningAudit->query,
                'audit_slug_id' => $event->screeningAudit->slugId,
                'credits_cost' => $event->cost,
            ],
            actor: $user->id->toString(),
            workspace: $event->workspace
        );

        $this->auditLogRepository->save($audit);
    }
}
