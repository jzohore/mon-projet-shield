<?php

namespace App\Application\Audit\DTO\Request;

use App\Domain\AuditLog\Enum\AuditEventType;
use Symfony\Component\Uid\Uuid;

class CreateAuditLogRequest
{
    /**
     * @param AuditEventType $eventName
     * @param array<string, int|string|Uuid> $data
     * @param string $actorId
     * @param string|null $workspaceId
     */
    public function __construct(
        public AuditEventType $eventName,
        public array $data,
        public string $actorId,
        public ?string $workspaceId = null,
    ) {}
}
