<?php

declare(strict_types=1);

namespace App\Infrastructure\Screening\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Screening\Event\ScreeningCompletedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class ScreeningAuditLogListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(ScreeningCompletedEvent $event): void
    {
        $user = $event->user;
        Assert::notNull($user->id);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::SCREENING_PERFORMED,
            payload: [
                'actor_name' => $user->getFullName(),
                'actor_email' => $user->email,
                'query_searched' => $event->screeningAudit->query,
                'audit_slug_id' => $event->screeningAudit->slugId,
                'total_matches' => $event->screeningAudit->totalMatches,
                'credits_cost' => $event->cost,
            ],
            workspace: $event->workspace
        );

        $this->auditLogRepository->save($audit);
    }
}
