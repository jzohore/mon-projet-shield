<?php

namespace App\Domain\Screening\Event;

readonly class ScreeningCompletedEvent
{
    public function __construct(
        public string $auditId,
        public string $workspaceSlugId,
        public string $userEmail,
        public string $query,
        public int $cost
    ) {}
}
