<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Listener;

use App\Domain\User\Event\AdminAccountActionOccurred;
use App\Infrastructure\User\Message\SendAdminAccountEmailMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Notification e-mail du membre concerné après une mutation de son compte. Le
 * lien pointe vers la page de connexion (magic link) : aucun jeton n'est
 * transporté dans l'e-mail. La modification de profil ne déclenche pas d'envoi.
 */
#[AsEventListener(event: AdminAccountActionOccurred::class)]
final readonly class SendAdminAccountEmailListener
{
    public function __construct(
        private UrlGeneratorInterface $router,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(AdminAccountActionOccurred $event): void
    {
        if (!$event->action->notifiesTarget()) {
            return;
        }

        $this->messageBus->dispatch(new SendAdminAccountEmailMessage(
            recipientEmail: $event->adminEmail,
            firstName: $event->adminFirstName,
            action: $event->action->value,
            loginUrl: $this->router->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL),
            context: $event->context,
        ));
    }
}
