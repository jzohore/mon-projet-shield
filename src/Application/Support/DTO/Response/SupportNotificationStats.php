<?php

namespace App\Application\Support\DTO\Response;

final readonly class SupportNotificationStats
{
    public function __construct(
        public bool $hasActiveThread = false,
        public int $unreadCount = 0
    ) {}
}
