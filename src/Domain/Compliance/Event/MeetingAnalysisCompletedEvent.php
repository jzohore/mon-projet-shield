<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

final readonly class MeetingAnalysisCompletedEvent
{
    public function __construct(
        public string $recordingId, // UUID du MeetingRecording
        public string $folderSlugId, // Pour cibler facilement le dossier client
    ) {
    }
}
