<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Billing\Event\CreditPurchasedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener(event: CreditPurchasedEvent::class)]
readonly class AuditCreditPurchaseListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(CreditPurchasedEvent $event): void
    {
        $user = $event->user;
        $workspace = $event->workspace;
        $transaction = $event->transaction;

        Assert::notNull($user->id);
        Assert::notNull($transaction->id);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::SUBSCRIPTION_TRIAL_EXTENDED,
            payload: [
                'workspace_name' => $workspace->name,
                'credits_added' => $transaction->amount,
                'new_balance' => $workspace->balance,
                'transaction_id' => $transaction->id,
                'actor_name' => $user->getFullName(),
                'actor_email' => $user->email,
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
