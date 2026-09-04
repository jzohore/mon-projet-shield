<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

/**
 * Un accusé de réception du DER a été révoqué par un administrateur du cabinet.
 */
final readonly class DerAcknowledgementRevokedEvent
{
    public function __construct(
        private string $documentId,
        private string $folderSlugId,
        private string $acknowledgementSlugId,
        private string $revokedByName,
        private string $reason,
    ) {
    }

    public function getDocumentId(): string
    {
        return $this->documentId;
    }

    public function getFolderSlugId(): string
    {
        return $this->folderSlugId;
    }

    public function getAcknowledgementSlugId(): string
    {
        return $this->acknowledgementSlugId;
    }

    public function getRevokedByName(): string
    {
        return $this->revokedByName;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
