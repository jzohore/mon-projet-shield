<?php

namespace App\Infrastructure\Billing\Listener\Subscription\Activated;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Billing\Event\SubscriptionActivatedEvent;
use Exception;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener(event: SubscriptionActivatedEvent::class)]
readonly class SubscriptionActivatedAuditListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(SubscriptionActivatedEvent $event): void
    {
        $user = $event->user;
        $workspace = $event->workspace;
        $subscription = $event->subscription;

        Assert::notNull($user->id);
        Assert::notNull($subscription->stripeSubscriptionId);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::SUBSCRIPTION_ACTIVATED,
            payload: [
                'workspace_name' => $workspace->name,
                'stripe_subscription_id' => $subscription->stripeSubscriptionId,
                'status' => $subscription->status,
            ],
            actor: $user->id->toString(),
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
