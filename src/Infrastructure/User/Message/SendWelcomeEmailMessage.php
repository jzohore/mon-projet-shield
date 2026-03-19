<?php

namespace App\Infrastructure\User\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
final readonly class SendWelcomeEmailMessage
{
    public function __construct(
        public ?string $userEmail,
    ) {}
}
