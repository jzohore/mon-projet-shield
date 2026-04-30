<?php

namespace App\Infrastructure\Billing\Listener\Subscription\Activated;

use App\Domain\Billing\Event\SubscriptionActivatedEvent;
use App\Infrastructure\Billing\Message\SendSubscriptionActivatedEmailMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

#[AsEventListener(event: SubscriptionActivatedEvent::class)]
readonly class SubscriptionActivatedEmailListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(SubscriptionActivatedEvent $event): void
    {
        Assert::notNull($event->workspace->name);

        $this->messageBus->dispatch(new SendSubscriptionActivatedEmailMessage(
            recipientEmail: $event->recipientEmail,
            workspaceName: $event->workspace->name,
        ));
    }
}
