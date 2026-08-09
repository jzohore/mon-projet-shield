<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Listener\Onboarding;

use App\Domain\User\Event\UserOnboardingCompletedEvent;
use App\Infrastructure\User\Message\SendOnboardingConfirmedMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

#[AsEventListener(event: UserOnboardingCompletedEvent::class)]
readonly class DispatchOnboardingConfirmedEmailListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(UserOnboardingCompletedEvent $event): void
    {
        $user = $event->user;
        $workspace = $event->workspace;

        Assert::notNull($user->id);
        Assert::notNull($workspace->id);

        $message = new SendOnboardingConfirmedMessage(
            userId: $user->id->toString(),
            workspaceId: $workspace->id->toString(),
        );

        $this->messageBus->dispatch($message);
    }
}
