<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

/**
 * Le client a déclaré ne pas reconnaître le DER depuis la page publique.
 */
final readonly class DerDeclinedEvent
{
    public function __construct(
        private string $documentId,
        private string $folderSlugId,
        private ?string $reason,
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

    public function getReason(): ?string
    {
        return $this->reason;
    }
}
