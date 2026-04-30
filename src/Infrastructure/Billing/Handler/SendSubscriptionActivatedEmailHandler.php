<?php

namespace App\Infrastructure\Billing\Handler;

use App\Infrastructure\Billing\Message\SendSubscriptionActivatedEmailMessage;
use App\Infrastructure\Notification\Email\Billing\DispatchSubscriptionActivatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class SendSubscriptionActivatedEmailHandler
{
    public function __construct(
        private MailerInterface $mailer
    ) {}

    /**
     * @param SendSubscriptionActivatedEmailMessage $message
     * @return void
     * @throws TransportExceptionInterface
     */
    public function __invoke(SendSubscriptionActivatedEmailMessage $message): void
    {
        $email = new DispatchSubscriptionActivatedEmail(
            recipientEmail: $message->recipientEmail,
            workspaceName: $message->workspaceName
        );

        $this->mailer->send($email);
    }
}
