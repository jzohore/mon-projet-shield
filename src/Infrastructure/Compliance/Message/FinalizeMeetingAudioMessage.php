<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Message;

final readonly class FinalizeMeetingAudioMessage
{
    public function __construct(
        public string $folderSlugId,
        public int $consumedSeconds,
    ) {
    }
}
