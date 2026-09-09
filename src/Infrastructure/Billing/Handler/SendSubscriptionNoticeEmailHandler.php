<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Handler;

use App\Infrastructure\Billing\Message\SendSubscriptionNoticeEmailMessage;
use App\Infrastructure\Notification\Email\Billing\SubscriptionNoticeEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class SendSubscriptionNoticeEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(SendSubscriptionNoticeEmailMessage $message): void
    {
        $this->mailer->send(new SubscriptionNoticeEmail(
            recipientEmail: $message->recipientEmail,
            subject: $message->subject,
            headline: $message->headline,
            body: $message->body,
            workspaceName: $message->workspaceName,
        ));
    }
}
