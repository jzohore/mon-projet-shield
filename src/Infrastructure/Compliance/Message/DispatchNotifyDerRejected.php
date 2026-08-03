<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
readonly class DispatchNotifyDerRejected
{
    public function __construct(
        private string $folderUrl,
        private string $folderId,
        private string $rejectedAt,
        private string $ownerEmail,
        private ?string $declineReason = null,
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

    public function getRejectedAt(): string
    {
        return $this->rejectedAt;
    }

    public function getOwnerEmail(): string
    {
        return $this->ownerEmail;
    }

    public function getDeclineReason(): ?string
    {
        return $this->declineReason;
    }
}
