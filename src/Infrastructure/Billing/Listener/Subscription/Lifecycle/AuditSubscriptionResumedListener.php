<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Listener\Subscription\Lifecycle;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Billing\Event\SubscriptionResumedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
readonly class AuditSubscriptionResumedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    public function __invoke(SubscriptionResumedEvent $event): void
    {
        $this->auditLogRepository->save(AuditLog::initiate(
            eventName: AuditEventType::SUBSCRIPTION_RESUMED,
            payload: [
                'stripe_subscription_id' => $event->subscription->stripeSubscriptionId,
                'plan_reference' => $event->subscription->planReference,
                'seats_count' => $event->subscription->seatsCount,
                'actor_name' => $event->user->getFullName(),
                'actor_email' => $event->user->email,
                'at' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
            ],
            workspace: $event->workspace,
        ));
    }
}
