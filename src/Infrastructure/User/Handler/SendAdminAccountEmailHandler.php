<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Handler;

use App\Domain\User\Enum\AdminAccountAction;
use App\Infrastructure\Notification\Email\Admin\AdminAccountNotificationEmail;
use App\Infrastructure\User\Message\SendAdminAccountEmailMessage;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendAdminAccountEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(SendAdminAccountEmailMessage $message): void
    {
        $action = AdminAccountAction::tryFrom($message->action);
        if (!$action instanceof AdminAccountAction || !$action->notifiesTarget()) {
            return;
        }

        $this->mailer->send(new AdminAccountNotificationEmail(
            recipientEmail: $message->recipientEmail,
            firstName: $message->firstName,
            action: $action,
            loginUrl: $message->loginUrl,
            context: $message->context,
        ));
    }
}
