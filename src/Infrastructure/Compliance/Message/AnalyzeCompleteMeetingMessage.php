<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Message;

readonly class AnalyzeCompleteMeetingMessage
{
    public function __construct(public string $folderSlugId, public string $audioFilePath, public int $consumedSeconds)
    {
    }
}
