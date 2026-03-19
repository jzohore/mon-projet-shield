<?php

namespace App\Infrastructure\User\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
final readonly class SendMagicLinkMessage
{
    public function __construct(
        public ?string $userEmail,
        public ?string $magicLinkToken
    ) {}
}
