<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Message;

readonly class SetupBillingModeMessage
{
    public function __construct(
        public string $workspaceId,
        public string $userId,
    ) {
    }
}
