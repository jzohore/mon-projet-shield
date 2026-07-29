<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
readonly class SendClientDerConfirmationMessage
{
    public function __construct(
        private string $loginPageUrl,
        private string $folderId,
    ) {
    }

    public function getLoginPageUrl(): string
    {
        return $this->loginPageUrl;
    }

    public function getFolderId(): string
    {
        return $this->folderId;
    }
}
