<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

readonly class MeetingReportValidatedEvent
{
    public function __construct(
        public string $reportId,
        public string $reportSlugId,
        public string $folderSlugId,
        public int $version,
        public string $validatedByEmail,
        public string $validatedByName,
        public bool $adjusted = false,
    ) {
    }
}
