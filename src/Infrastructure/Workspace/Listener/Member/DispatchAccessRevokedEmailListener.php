<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Listener\Member;

use App\Domain\Workspace\Event\WorkspaceMemberRevokedEvent;
use App\Infrastructure\Workspace\Message\SendAccessRevokedEmailMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

#[AsEventListener(event: WorkspaceMemberRevokedEvent::class)]
readonly class DispatchAccessRevokedEmailListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(WorkspaceMemberRevokedEvent $event): void
    {
        $user = $event->revokedUser;
        $workspace = $event->workspace;

        Assert::notNull($user->id);

        $message = new SendAccessRevokedEmailMessage(
            userId: $user->id->toString(),
            workspaceName: $workspace->name,
        );

        $this->messageBus->dispatch($message);
    }
}
