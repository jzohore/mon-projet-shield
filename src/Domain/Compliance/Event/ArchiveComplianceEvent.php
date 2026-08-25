<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ArchiveComplianceEvent extends Event
{
    public function __construct(
        public readonly string $folderSlugId,
        public readonly string $userSlugId,
    ) {
    }
}
