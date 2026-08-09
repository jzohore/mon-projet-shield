<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Listener\Subscription\Canceled;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Billing\Event\SubscriptionCanceledEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class SubscriptionCanceledAuditListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    public function __invoke(SubscriptionCanceledEvent $event): void
    {
        $user = $event->user;
        $workspace = $event->workspace;
        $subscription = $event->subscription;

        Assert::notNull($user->id);
        Assert::notNull($subscription->stripeSubscriptionId);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::SUBSCRIPTION_CANCELED,
            payload: [
                'reason' => $subscription->reason,
                'end_date' => $subscription->currentPeriodEnd->format('d/m/Y'),
                'actor_name' => $user->getFullName(),
                'actor_email' => $user->email,
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
