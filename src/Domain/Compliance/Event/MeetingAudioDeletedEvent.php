<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

readonly class MeetingAudioDeletedEvent
{
    public function __construct(
        public string $recordingId,
        public string $filePath,
        public string $deletedByEmail,
    ) {
    }
}
