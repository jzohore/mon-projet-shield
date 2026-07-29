<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
readonly class DispatchNotifyDerSigned
{
    public function __construct(
        private string $folderUrl,
        private string $folderId,
        private string $signedAt,
        private string $ownerEmail,
    ) {
    }

    public function getFolderUrl(): string
    {
        return $this->folderUrl;
    }

    public function getFolderId(): string
    {
        return $this->folderId;
    }

    public function getSignedAt(): string
    {
        return $this->signedAt;
    }

    public function getOwnerEmail(): string
    {
        return $this->ownerEmail;
    }
}
