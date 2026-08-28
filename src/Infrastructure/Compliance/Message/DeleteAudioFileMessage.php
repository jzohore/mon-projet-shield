<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Message;

readonly class DeleteAudioFileMessage
{
    public function __construct(
        public string $filePath,
    ) {
    }
}
