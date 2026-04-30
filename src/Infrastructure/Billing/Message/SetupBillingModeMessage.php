<?php

namespace App\Infrastructure\Billing\Message;

use Symfony\Component\Uid\Uuid;

readonly class SetupBillingModeMessage
{
    public function __construct(
        public Uuid $workspaceId,
        public string $userId,
    ) {}
}
