<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

final readonly class DerSentEvent
{
    public function __construct(
        private string $folderId,
        private string $triggeredByUserId,
    ) {
    }

    public function getFolderId(): string
    {
        return $this->folderId;
    }

    public function getTriggeredByUserId(): string
    {
        return $this->triggeredByUserId;
    }
}
