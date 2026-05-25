<?php

namespace App\Infrastructure\Screening\Message;

readonly class ShareDocumentMessage
{
    public function __construct(
        public string $recipientEmail,
        public string $auditId,
        public string $senderId,
    ) {}
}
