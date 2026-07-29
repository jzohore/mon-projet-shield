<?php

declare(strict_types=1);

namespace App\Domain\Kyc\Event;

use App\Domain\Kyc\Entity\KycFolder;
use Symfony\Contracts\EventDispatcher\Event;

final class BindCompanyEvent extends Event
{
    public function __construct(
        public readonly KycFolder $kycFolder,
    ) {
    }
}
