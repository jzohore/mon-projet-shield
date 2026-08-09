<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Message;

use App\Domain\User\Enum\UserType;

final readonly class SendMagicLinkMessage
{
    public function __construct(
        public string $email,
        public string $magicLinkToken,
        public UserType $recipientType,
    ) {
    }
}
