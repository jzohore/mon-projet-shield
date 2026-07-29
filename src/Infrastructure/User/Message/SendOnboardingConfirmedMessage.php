<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
final readonly class SendOnboardingConfirmedMessage
{
    public function __construct(
        public string $userId,
        public string $workspaceId,
    ) {
    }
}
