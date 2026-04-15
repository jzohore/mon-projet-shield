<?php

namespace App\Domain\Screening\Event;

readonly class DocumentSharedEvent
{
    /** @param array<int, string> $recipients */
    public function __construct(
        public string $auditId,
        public string $workspaceSlugId,
        public string $userEmail,
        public array $recipients,
    ) {}
}
