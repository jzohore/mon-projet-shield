<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

/**
 * Le client a accusé réception du DER (remplace {@see DerSignedEvent}, lié à
 * DocuSeal). Scalaires uniquement : rejouable, sérialisable.
 */
final readonly class DerAcknowledgedEvent
{
    public function __construct(
        private string $documentId,
        private string $folderSlugId,
        private string $acknowledgementSlugId,
        private string $declaredName,
        private \DateTimeImmutable $acknowledgedAt,
        private string $pdfSha256,
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

    public function getDeclaredName(): string
    {
        return $this->declaredName;
    }

    public function getAcknowledgedAt(): \DateTimeImmutable
    {
        return $this->acknowledgedAt;
    }

    public function getPdfSha256(): string
    {
        return $this->pdfSha256;
    }
}
