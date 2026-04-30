<?php

namespace App\Infrastructure\Billing\Listener\Subscription\Canceled;

use App\Domain\Billing\Event\SubscriptionCanceledEvent;
use App\Infrastructure\Billing\Message\SendSubscriptionCanceledEmailMessage;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

#[AsEventListener(event: SubscriptionCanceledEvent::class)]
readonly class SubscriptionCanceledEmailListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private Security $security,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(SubscriptionCanceledEvent $event): void
    {
        Assert::notNull($event->subscription->workspace->name);
        $user = $this->security->getUser();
        Assert::notNull($user);
        $recipientEmail = $user->getUserIdentifier();
        $this->messageBus->dispatch(new SendSubscriptionCanceledEmailMessage(
            recipientEmail: $recipientEmail,
            workspaceName: $event->subscription->workspace->name,
            endDate: $event->subscription->currentPeriodEnd->format('d/m/Y'),
        ));
    }
}
