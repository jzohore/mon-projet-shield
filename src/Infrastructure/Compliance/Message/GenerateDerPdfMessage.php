<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Message;

readonly class GenerateDerPdfMessage
{
    public function __construct(
        public string $documentId,
        public ?string $oldStoragePath = null,
    ) {
    }
}
