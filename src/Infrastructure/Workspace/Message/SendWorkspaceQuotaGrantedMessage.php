<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
final readonly class SendWorkspaceQuotaGrantedMessage
{
    public function __construct(
        public string $recipientEmail,
        public string $workspaceName,
        public int $dossiersGranted,
        public int $minutesGranted,
        public int $trialDossiersRemaining,
        public int $remainingMinutes,
    ) {
    }
}
