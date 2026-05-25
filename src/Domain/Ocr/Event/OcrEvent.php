<?php

declare(strict_types=1);

namespace App\Domain\Ocr\Event;

use App\Domain\Kyc\Entity\KycDocument;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Contracts\EventDispatcher\Event;

final class OcrEvent extends Event
{
    public function __construct(
        public readonly KycDocument $kycDocument,
        public readonly User $user,
        public readonly Workspace $workspace,
    ) {}
}
