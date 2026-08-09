<?php

declare(strict_types=1);

namespace App\Infrastructure\KYC\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
readonly class SendCreatedKycFolderMessage
{
    public function __construct(
        public string $slugId,
        public string $actionUrl,
    ) {
    }
}
