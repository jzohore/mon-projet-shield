<?php

namespace App\Application\Audit\DTO\Request;

use App\Domain\AuditLog\Enum\AuditEventType;

class CreateAuditLogRequest
{
    public function __construct(
        public ?AuditEventType $eventName = null,
        public ?string $resourceId = null,
        public ?array $data = null,
    ) {}
}
