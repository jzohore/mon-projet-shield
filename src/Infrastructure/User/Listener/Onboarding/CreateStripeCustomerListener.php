<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Listener\Onboarding;

use App\Domain\Workspace\Event\WorkspacePlanSelectedEvent;
use App\Infrastructure\User\Message\CreateStripeCustomerMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

/**
 * Dès que le type de compte est choisi, on planifie la création du client Stripe
 * en tâche de fond (l'appel API ne doit pas bloquer l'onboarding ni le faire
 * échouer si Stripe est lent/indisponible). Le handler est idempotent.
 */
#[AsEventListener(event: WorkspacePlanSelectedEvent::class)]
readonly class CreateStripeCustomerListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(WorkspacePlanSelectedEvent $event): void
    {
        $user = $event->user;
        Assert::notNull($user->id);

        $this->messageBus->dispatch(new CreateStripeCustomerMessage($user->id->toString()));
    }
}
