<?php

namespace App\Infrastructure\Billing\Listener;

use App\Domain\Billing\Event\CreditPurchasedEvent;
use App\Infrastructure\Billing\Message\SendPurchaseConfirmationEmailMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class PurchaseEmailListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(CreditPurchasedEvent $event): void
    {
        Assert::notNull($event->user->email);
        Assert::notNull($event->workspace->name);
        $this->messageBus->dispatch(new SendPurchaseConfirmationEmailMessage(
            email: $event->user->email,
            workspaceName: $event->workspace->name,
            credits: $event->transaction->amount,
            invoiceUrl: $event->invoiceUrl
        ));
    }
}
