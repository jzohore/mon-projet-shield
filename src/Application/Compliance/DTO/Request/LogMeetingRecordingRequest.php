<?php

declare(strict_types=1);

namespace App\Application\Compliance\DTO\Request;

final readonly class LogMeetingRecordingRequest
{
    public function __construct(
        public string $folderSlugId,
        public string $sessionId,
        public string $s3Path,
        public int $consumedSeconds,
    ) {
    }
}
