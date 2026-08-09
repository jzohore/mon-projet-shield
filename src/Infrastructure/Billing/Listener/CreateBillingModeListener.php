<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Listener;

use App\Domain\Billing\Event\CreateBillingModeEvent;
use App\Infrastructure\Billing\Message\SetupBillingModeMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

readonly class CreateBillingModeListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    #[AsEventListener]
    public function onBillingModeCreated(CreateBillingModeEvent $event): void
    {
        $user = $event->user;
        $workspace = $event->workspace;

        Assert::notNull($user->id);
        Assert::notNull($workspace->id);

        $this->messageBus->dispatch(new SetupBillingModeMessage(
            workspaceId: $workspace->id->toString(),
            userId: $user->id->toString(),
        ));
    }
}
