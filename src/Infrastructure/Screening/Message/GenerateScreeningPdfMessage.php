<?php

namespace App\Infrastructure\Screening\Message;

readonly class GenerateScreeningPdfMessage
{
    public function __construct(
        public string $auditId,
        public ?string    $oldStoragePath = null
    ) {}
}
