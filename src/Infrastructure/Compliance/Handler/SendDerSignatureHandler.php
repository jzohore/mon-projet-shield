<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Handler;

use App\Infrastructure\Compliance\Message\SendDerSignatureMessage;
use App\Infrastructure\Notification\Email\Compliance\DerSignatureEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class SendDerSignatureHandler
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(SendDerSignatureMessage $message): void
    {
        $email = new DerSignatureEmail(
            $message->clientEmail,
            $message->clientName,
            $message->signatureUrl
        );

        $this->mailer->send($email);
    }
}
