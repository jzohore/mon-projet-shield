<?php

namespace App\Domain\Kyc\Event;

use App\Domain\Kyc\Entity\KycDocument;
use App\Domain\Kyc\Entity\KycFolder;
use Symfony\Contracts\EventDispatcher\Event;

final class CompanyResetEvent extends Event
{
    /**
     * @param array<int, KycDocument> $oldDocuments
     */
    public function __construct(
        public readonly KycFolder $folder,
        public array     $oldDocuments
    ) {}
}
