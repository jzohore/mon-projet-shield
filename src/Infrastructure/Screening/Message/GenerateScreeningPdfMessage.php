<?php

declare(strict_types=1);

namespace App\Infrastructure\Screening\Message;

readonly class GenerateScreeningPdfMessage
{
    public function __construct(
        public string $auditId,
        public ?string $oldStoragePath = null,
    ) {
    }
}
