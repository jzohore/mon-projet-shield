<?php

namespace App\Infrastructure\Billing\Listener\Subscription\Canceled;

use App\Application\Audit\DTO\Request\CreateAuditLogRequest;
use App\Application\Audit\UseCase\CreateAuditLogUseCase;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\Billing\Event\SubscriptionCanceledEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: SubscriptionCanceledEvent::class)]
readonly class SubscriptionCanceledAuditListener
{
    public function __construct(
        private CreateAuditLogUseCase $auditLogUseCase,
    ) {}

    public function __invoke(SubscriptionCanceledEvent $event): void
    {
        $auditLog = new CreateAuditLogRequest(
            eventName: AuditEventType::SUBSCRIPTION_ACTIVATED,
            resourceId: $event->subscription->workspace->slugId,
            data: [
                'reason' => $event->reason,
                'end_date' => $event->subscription->currentPeriodEnd->format('d/m/Y'),
            ]
        );

        ($this->auditLogUseCase)($auditLog);
    }
}
