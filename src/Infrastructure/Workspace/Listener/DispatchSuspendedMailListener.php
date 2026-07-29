<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Listener;

use App\Domain\Workspace\Event\WorkspaceSuspendedEvent;
use App\Infrastructure\Workspace\Message\DispatchSuspendedEmailMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class DispatchSuspendedMailListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(WorkspaceSuspendedEvent $event): void
    {
        $workspace = $event->workspace;
        Assert::notNull($workspace->id);

        $user = $event->user;
        Assert::notNull($user->id);

        $url = $this->urlGenerator->generate('app_settings_organization', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $message = new DispatchSuspendedEmailMessage(
            workspaceId: $workspace->id->toString(),
            userId: $user->id->toString(),
            actionUrl: $url,
        );

        $this->messageBus->dispatch($message);
    }
}
