<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

/**
 * Événement déclenché lorsqu'un flux audio fragmenté a été fusionné et sécurisé sur S3.
 */
final readonly class MeetingAudioFinalizedEvent
{
    public function __construct(
        public string $folderSlugId,
        public string $sessionId,
        public string $s3Path,
        public int $consumedSeconds,
    ) {
    }
}
