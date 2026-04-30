<?php

namespace App\Infrastructure\Billing\Listener;

use App\Application\Audit\DTO\Request\CreateAuditLogRequest;
use App\Application\Audit\UseCase\CreateAuditLogUseCase;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\Billing\Event\CreditPurchasedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: CreditPurchasedEvent::class)]
readonly class AuditCreditPurchaseListener
{
    public function __construct(
        private CreateAuditLogUseCase $auditLogUseCase,
    ) {}

    public function __invoke(CreditPurchasedEvent $event): void
    {
        $auditLog = new CreateAuditLogRequest(
            eventName: AuditEventType::CREDIT_PURCHASED, // À ajouter dans ton enum
            resourceId: $event->workspace->slugId,
            data: [
                'workspace_name' => $event->workspace->name,
                'credits_added' => $event->transaction->amount,
                'new_balance' => $event->workspace->balance,
                'transaction_id' => $event->transaction->id,
            ]
        );

        ($this->auditLogUseCase)($auditLog);
    }
}
