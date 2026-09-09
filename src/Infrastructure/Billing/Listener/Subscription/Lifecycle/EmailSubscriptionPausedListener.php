<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Listener\Subscription\Lifecycle;

use App\Domain\Billing\Event\SubscriptionPausedEvent;
use App\Infrastructure\Billing\Message\SendSubscriptionNoticeEmailMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener]
readonly class EmailSubscriptionPausedListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SubscriptionPausedEvent $event): void
    {
        $email = trim($event->user->email);
        if ('' === $email) {
            $this->logger->warning('Notification abonnement ignorée : e-mail destinataire absent.', [
                'workspace_slug_id' => $event->workspace->slugId,
            ]);

            return;
        }

        $this->messageBus->dispatch(new SendSubscriptionNoticeEmailMessage(
            recipientEmail: $email,
            workspaceName: $event->workspace->name,
            subject: 'Votre abonnement KYSURE est suspendu',
            headline: 'Abonnement suspendu',
            body: 'Votre abonnement est désormais suspendu : aucune facture ne sera émise tant que la suspension dure, et l\'accès aux fonctionnalités est mis en pause. Vous pouvez le réactiver à tout moment, sans avoir à re-souscrire.',
        ));
    }
}
