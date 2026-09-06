<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Handler;

use App\Infrastructure\Compliance\Message\SendClientRelationshipEndedMessage;
use App\Infrastructure\Notification\Email\Client\ClientRelationshipEndedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class SendClientRelationshipEndedHandler
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(SendClientRelationshipEndedMessage $message): void
    {
        $this->mailer->send(new ClientRelationshipEndedEmail(
            email: $message->clientEmail,
            clientName: $message->clientName,
            workspaceName: $message->workspaceName,
            workspaceContactEmail: $message->workspaceContactEmail,
            purgeDueAtFormatted: $message->purgeDueAtFormatted,
        ));
    }
}
