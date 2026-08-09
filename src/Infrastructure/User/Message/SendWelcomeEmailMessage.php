<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Message;

final readonly class SendWelcomeEmailMessage
{
    public function __construct(
        public string $userId,
        public string $email,
        public string $fullName,
        public string $magicLinkToken,
    ) {
    }
}
