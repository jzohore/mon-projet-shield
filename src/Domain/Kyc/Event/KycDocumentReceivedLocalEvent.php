<?php

namespace App\Domain\Kyc\Event;

use App\Domain\Kyc\Entity\KycDocument;
use App\Domain\Kyc\Entity\KycFolder;
use Symfony\Contracts\EventDispatcher\Event;

class KycDocumentReceivedLocalEvent extends Event
{
    public function __construct(
        public readonly KycDocument $kycDocument,
        public readonly KycFolder $kycFolder,
        public readonly string $localTempPath,
        public readonly string $mimeType,
        public readonly string $originalName,
        public readonly ?string $oldStoragePath = null
    ) {}
}
