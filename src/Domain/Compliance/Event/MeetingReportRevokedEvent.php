<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

readonly class MeetingReportRevokedEvent
{
    public function __construct(
        public string $reportId,
        public string $reportSlugId,
        public string $folderSlugId,
        public int $version,
        public string $reason,
        public string $revokedByEmail,
        public string $revokedByName,
    ) {
    }
}
