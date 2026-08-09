<?php

declare(strict_types=1);

namespace App\Application\Audit\DTO\Response;

readonly class AuditLogView
{
    public function __construct(
        public string $eventName,
        public string $actor,
        /** @var array<string, int> */
        public array $payload,
        public string $occurredAt,
    ) {
    }
}
