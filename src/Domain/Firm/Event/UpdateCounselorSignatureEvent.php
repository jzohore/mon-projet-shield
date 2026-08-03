<?php

declare(strict_types=1);

namespace App\Domain\Firm\Event;

use App\Domain\Firm\Entity\RegulatoryProfile;
use App\Domain\User\Entity\User;

final readonly class UpdateCounselorSignatureEvent
{
    public function __construct(
        public RegulatoryProfile $profile,
        public User $updatedBy,
    ) {
    }

    public function getProfile(): RegulatoryProfile
    {
        return $this->profile;
    }

    public function getUpdatedBy(): User
    {
        return $this->updatedBy;
    }
}
