<?php

namespace App\Infrastructure\User\Listener\UserCreated;

use App\Domain\Shared\Service\GenerateLinkToken;
use App\Domain\User\Event\UserCreatedEvent;
use App\Infrastructure\User\Message\SendWelcomeEmailMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

#[AsEventListener(event: UserCreatedEvent::class)]
readonly class DispatchWelcomeEmailListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private GenerateLinkToken $generateLinkToken,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    #[AsEventListener]
    public function __invoke(UserCreatedEvent $event): void
    {
        $user = $event->user;

        Assert::notNull($user->magicLinkToken);
        Assert::notNull($user->id);

        $magicLinkUrl = $this->generateLinkToken->generate(
            routeName: 'app_verify_magic_link',
            magicLinkToken: $user->magicLinkToken
        );

        $message = new SendWelcomeEmailMessage(
            userId: $user->id->toString(),
            magicLinkUrl: $magicLinkUrl,
        );

        $this->messageBus->dispatch($message);
    }
}
