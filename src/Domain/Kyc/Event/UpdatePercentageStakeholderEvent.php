<?php

namespace App\Domain\Kyc\Event;

use App\Domain\Kyc\Entity\KycFolder;
use App\Domain\Kyc\Entity\Stakeholder;
use Symfony\Contracts\EventDispatcher\Event;

final class UpdatePercentageStakeholderEvent extends Event
{
    public function __construct(
        public readonly KycFolder $kycFolder,
        public readonly Stakeholder $stakeholder,
    ) {}
}
