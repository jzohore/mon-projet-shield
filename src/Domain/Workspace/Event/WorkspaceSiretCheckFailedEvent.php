<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Event;

use App\Domain\Workspace\Entity\Workspace;

final readonly class WorkspaceSiretCheckFailedEvent
{
    public function __construct(
        public Workspace $workspace,
        public string $siretNumber,
        public string $errorMessage,
        public string $performedByEmail,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }
}
