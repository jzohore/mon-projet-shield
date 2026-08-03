<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
final readonly class SendOnboardingReminderMessage
{
    public function __construct(
        public string $userEmail,
    ) {
    }
}
