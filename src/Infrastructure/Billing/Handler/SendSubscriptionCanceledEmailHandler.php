<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Handler;

use App\Infrastructure\Billing\Message\SendSubscriptionCanceledEmailMessage;
use App\Infrastructure\Notification\Email\Billing\DispatchSubscriptionCanceledEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class SendSubscriptionCanceledEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(SendSubscriptionCanceledEmailMessage $message): void
    {
        $email = new DispatchSubscriptionCanceledEmail(
            recipientEmail: $message->recipientEmail,
            workspaceName: $message->workspaceName,
            end_date: $message->endDate,
        );

        $this->mailer->send($email);
    }
}
