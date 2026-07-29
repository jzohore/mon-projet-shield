<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Message;

readonly class SendDerSignatureMessage
{
    public function __construct(
        public string $clientEmail,
        public string $clientName,
        public string $signatureUrl,
    ) {
    }
}
