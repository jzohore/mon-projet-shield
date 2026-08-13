<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Listener;

use App\Domain\Workspace\Event\WorkspaceSuspendedEvent;
use App\Infrastructure\Workspace\Message\DispatchSuspendedEmailMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class DispatchSuspendedMailListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(WorkspaceSuspendedEvent $event): void
    {
        $workspace = $event->workspace;
        Assert::notNull($workspace->id);

        $message = new DispatchSuspendedEmailMessage(
            workspaceId: $workspace->id->toString(),
        );

        $this->messageBus->dispatch($message);
    }
}
