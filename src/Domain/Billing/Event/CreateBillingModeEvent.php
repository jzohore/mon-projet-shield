<?php

declare(strict_types=1);

namespace App\Domain\Billing\Event;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Contracts\EventDispatcher\Event;

class CreateBillingModeEvent extends Event
{
    public function __construct(
        public readonly Workspace $workspace,
        public readonly User $user,
    ) {
    }
}
