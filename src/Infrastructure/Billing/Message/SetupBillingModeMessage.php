<?php

namespace App\Infrastructure\Billing\Message;

readonly class SetupBillingModeMessage
{
    /**
     * @param string $workspaceId
     * @param string $userId
     */
    public function __construct(
        public string $workspaceId,
        public string $userId,
    ) {}
}
