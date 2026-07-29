<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Message;

readonly class SendAccessRevokedEmailMessage
{
    public function __construct(
        public string $userId,
        public string $workspaceName,
    ) {
    }
}
