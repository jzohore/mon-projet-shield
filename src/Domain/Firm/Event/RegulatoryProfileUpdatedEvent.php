<?php

declare(strict_types=1);

namespace App\Domain\Firm\Event;

use App\Domain\Firm\Entity\RegulatoryProfile;
use App\Domain\User\Entity\User;

readonly class RegulatoryProfileUpdatedEvent
{
    public function __construct(
        public RegulatoryProfile $profile,
        public User $updatedBy,
    ) {
    }
}
