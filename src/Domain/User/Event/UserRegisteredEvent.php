<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

readonly class UserRegisteredEvent
{
    public function __construct(
        public string $userId,
        public string $email,
        public string $fullName,
        public ?string $magicLinkToken,
    ) {
    }
}
