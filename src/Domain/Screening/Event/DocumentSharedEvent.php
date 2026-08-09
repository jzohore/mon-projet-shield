<?php

declare(strict_types=1);

namespace App\Domain\Screening\Event;

use App\Domain\Screening\Entity\ScreeningAudit;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Contracts\EventDispatcher\Event;

class DocumentSharedEvent extends Event
{
    /** @param array<int, string> $recipients */
    public function __construct(
        public readonly ScreeningAudit $audit,
        public readonly Workspace $workspace,
        public readonly User $user,
        public array $recipients,
    ) {
    }
}
