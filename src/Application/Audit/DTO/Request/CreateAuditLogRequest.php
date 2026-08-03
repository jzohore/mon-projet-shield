<?php

declare(strict_types=1);

namespace App\Application\Audit\DTO\Request;

use App\Domain\AuditLog\Enum\AuditEventType;
use Symfony\Component\Uid\Uuid;

class CreateAuditLogRequest
{
    /**
     * @param array<string, int|string|Uuid> $data
     */
    public function __construct(
        public AuditEventType $eventName,
        public array $data,
        public string $actorId,
        public ?string $workspaceId = null,
    ) {
    }
}
