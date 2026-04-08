<?php

declare(strict_types=1);

namespace App\Domain\Ocr\Event;

use App\Domain\Kyc\Entity\KycDocument;
use Symfony\Contracts\EventDispatcher\Event;

final class OcrEvent extends Event
{
    public function __construct(
        public readonly KycDocument $kycDocument,
    ) {}
}
