<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Event;

use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\ValueObject\OriasStatusResult;

final readonly class WorkspaceOriasCheckSucceededEvent
{
    public function __construct(
        public Workspace $workspace,
        public OriasStatusResult $oriasResult,
        public string $performedByEmail,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }
}
