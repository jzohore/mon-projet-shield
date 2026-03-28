<?php

namespace App\Infrastructure\KYC\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
readonly class SendSubmittedKycFolderMessage
{
    public function __construct(
        public string $slugId,
        public string $actionUrl,
    ) {}
}
