<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Listener\Subscription\Lifecycle;

use App\Domain\Billing\Event\RetentionOfferClaimedEvent;
use App\Infrastructure\Billing\Message\SendSubscriptionNoticeEmailMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener]
readonly class EmailRetentionOfferClaimedListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(RetentionOfferClaimedEvent $event): void
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
            subject: 'Votre remise de fidélité KYSURE est appliquée',
            headline: 'Offre de fidélité appliquée',
            body: 'Bonne nouvelle : une remise de 30 % est appliquée à vos 3 prochaines factures. Toute résiliation programmée a été annulée — votre abonnement continue normalement.',
        ));
    }
}
