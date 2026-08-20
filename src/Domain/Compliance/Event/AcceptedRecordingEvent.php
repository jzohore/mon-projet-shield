<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

class AcceptedRecordingEvent
{
    public function __construct(
        public string $folderSlugId,
        public string $userSlugId,
    ) {
    }
}
