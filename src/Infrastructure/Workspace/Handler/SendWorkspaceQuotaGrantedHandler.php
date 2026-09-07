<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Handler;

use App\Infrastructure\Notification\Email\Workspace\WorkspaceQuotaGrantedEmail;
use App\Infrastructure\Workspace\Message\SendWorkspaceQuotaGrantedMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class SendWorkspaceQuotaGrantedHandler
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(SendWorkspaceQuotaGrantedMessage $message): void
    {
        $this->mailer->send(new WorkspaceQuotaGrantedEmail(
            email: $message->recipientEmail,
            workspaceName: $message->workspaceName,
            dossiersGranted: $message->dossiersGranted,
            minutesGranted: $message->minutesGranted,
            trialDossiersRemaining: $message->trialDossiersRemaining,
            remainingMinutes: $message->remainingMinutes,
        ));
    }
}
