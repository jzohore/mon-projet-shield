<?php

declare(strict_types=1);

namespace App\Infrastructure\Ocr\Message;

readonly class ProcessOcrMessage
{
    public function __construct(
        public string $documentSlugId,
    ) {
    }
}
