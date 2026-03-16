<?php

namespace App\Infrastructure\User\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
final readonly class SendMagicLinkMessage
{
    public function __construct(
        public string $userEmail, // Identifiant unique
        public string $magicLinkToken
    ) {}
}
