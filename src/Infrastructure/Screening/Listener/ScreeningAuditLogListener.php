<?php

namespace App\Infrastructure\Screening\Listener;

use App\Application\Audit\DTO\Request\CreateAuditLogRequest;
use App\Application\Audit\UseCase\CreateAuditLogUseCase;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\Screening\Event\ScreeningCompletedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
readonly class ScreeningAuditLogListener
{
    public function __construct(
        private CreateAuditLogUseCase $auditLogUseCase,
    ) {}

    public function __invoke(ScreeningCompletedEvent $event): void
    {
        $auditLog = new CreateAuditLogRequest(
            eventName: AuditEventType::SCREENING_PERFORMED,
            resourceId: $event->workspaceSlugId,
            data: [
                'user_email' => $event->userEmail,
                'query_searched' => $event->query,
                'audit_id' => $event->auditId,
                'credits_cost' => $event->cost,
            ]
        );
        ($this->auditLogUseCase)($auditLog);
    }
}
