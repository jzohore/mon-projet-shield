<?php

namespace App\Domain\Screening\Event;

use App\Domain\Screening\Entity\ScreeningAudit;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;

readonly class ScreeningCompletedEvent
{
    public function __construct(
        public Workspace $workspace,
        public User $user,
        public ScreeningAudit $screeningAudit,
        public int $cost,
    ) {}
}
