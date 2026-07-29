<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Listener\Register;

use App\Domain\User\Event\UserRegisteredEvent;
use App\Infrastructure\User\Message\SendWelcomeEmailMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class DispatchWelcomeEmailListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(UserRegisteredEvent $event): void
    {
        Assert::notNull($event->magicLinkToken);
        $message = new SendWelcomeEmailMessage(
            userId: $event->userId,
            email: $event->email,
            fullName: $event->fullName,
            magicLinkToken: $event->magicLinkToken,
        );

        $this->messageBus->dispatch($message);
    }
}
