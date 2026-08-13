<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Message;

final readonly class DispatchSuspendedEmailMessage
{
    public function __construct(
        public string $workspaceId,
    ) {
    }
}
