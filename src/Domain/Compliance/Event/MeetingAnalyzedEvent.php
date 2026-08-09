<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

readonly class MeetingAnalyzedEvent
{
    public function __construct(public string $folderSlugId, public string $audioFilePath, public int $consumedSeconds)
    {
    }
}
