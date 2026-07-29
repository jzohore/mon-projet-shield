<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
final readonly class DispatchSuspendedEmailMessage
{
    public function __construct(
        public string $workspaceId,
        public string $userId,
        public string $actionUrl,
    ) {
    }
}
