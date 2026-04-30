<?php

namespace App\Infrastructure\Billing\Listener;

use App\Domain\Billing\Event\CreateBillingModeEvent;
use App\Infrastructure\Billing\Message\SetupBillingModeMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

readonly class CreateBillingModeListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {}

    #[AsEventListener]
    public function onBillingModeCreated(CreateBillingModeEvent $event): void
    {
        $workspace = $event->workspace;
        $user = $event->user;

        Assert::notNull($workspace->id);
        Assert::notNull($user->slugId);
        // On envoie la tâche lourde dans la file d'attente (Asynchrone)
        $this->messageBus->dispatch(new SetupBillingModeMessage(
            $workspace->id, // ou $workspace->slugId selon ton entité
            $user->slugId
        ));
    }
}
