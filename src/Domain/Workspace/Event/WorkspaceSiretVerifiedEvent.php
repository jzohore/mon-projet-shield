<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Event;

use App\Domain\Workspace\Entity\Workspace;

final readonly class WorkspaceSiretVerifiedEvent
{
    public function __construct(
        public Workspace $workspace,
        public string $messageResult,
        public string $performedByEmail,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }
}
