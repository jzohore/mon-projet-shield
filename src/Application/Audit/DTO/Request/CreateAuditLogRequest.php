<?php

namespace App\Application\Audit\DTO\Request;

use App\Domain\AuditLog\Enum\AuditEventType;

class CreateAuditLogRequest
{
    /**
     * @param AuditEventType|null $eventName
     * @param string|null $resourceId
     * @param array<array-key, mixed> $data
     */
    public function __construct(
        public ?AuditEventType $eventName = null,
        public ?string $resourceId = null,
        public ?array $data = null,
    ) {}
}
