<?php

namespace App\Domain\Kyc\Event;

use App\Domain\Kyc\Entity\KycFolder;
use Symfony\Contracts\EventDispatcher\Event;

final class KycFolderSubmittedEvent extends Event
{
    public function __construct(
        public readonly KycFolder $kycFolder,
    ) {}
}
