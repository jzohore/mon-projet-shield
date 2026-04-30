<?php

namespace App\Infrastructure\Billing\Listener\Subscription\Activated;

use App\Application\Audit\DTO\Request\CreateAuditLogRequest;
use App\Application\Audit\UseCase\CreateAuditLogUseCase;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\Billing\Event\SubscriptionActivatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: SubscriptionActivatedEvent::class)]
readonly class SubscriptionActivatedAuditListener
{
    public function __construct(
        private CreateAuditLogUseCase $auditLogUseCase,
    ) {}

    public function __invoke(SubscriptionActivatedEvent $event): void
    {
        $auditLog = new CreateAuditLogRequest(
            eventName: AuditEventType::SUBSCRIPTION_ACTIVATED,
            resourceId: $event->workspace->slugId,
            data: [
                'workspace_name' => $event->workspace->name,
                'stripe_subscription_id' => $event->subscription->stripeSubscriptionId,
                'status' => $event->subscription->status,
            ]
        );

        ($this->auditLogUseCase)($auditLog);
    }
}
