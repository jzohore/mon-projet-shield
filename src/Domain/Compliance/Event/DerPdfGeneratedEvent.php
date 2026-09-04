<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

/**
 * Le PDF du DER vient d'être généré et stocké. Déclenche l'envoi du lien
 * d'accusé de réception si une demande d'envoi est en attente.
 */
final readonly class DerPdfGeneratedEvent
{
    public function __construct(private string $documentId)
    {
    }

    public function getDocumentId(): string
    {
        return $this->documentId;
    }
}
