<?php

namespace App\Infrastructure\Billing\Handler;

use App\Infrastructure\Billing\Message\SendPurchaseConfirmationEmailMessage;
use App\Infrastructure\Notification\Email\Billing\DispatchPurchaseConfirmationEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class SendPurchaseConfirmationEmailHandler
{
    public function __construct(
        private MailerInterface $mailer
    ) {}

    public function __invoke(SendPurchaseConfirmationEmailMessage $message): void
    {
        // On instancie l'email physique (Infrastructure) avec les données du message
        $email = new DispatchPurchaseConfirmationEmail(
            recipientEmail: $message->email,
            workspaceName: $message->workspaceName,
            credits: $message->credits,
            invoiceUrl: $message->invoiceUrl
        );

        // Envoi réel
        $this->mailer->send($email);
    }
}
