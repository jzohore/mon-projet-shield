<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

/**
 * Un verrou de litige a été posé ou levé sur un dossier (bloque / débloque la
 * purge).
 */
final readonly class FolderLegalHoldChangedEvent
{
    public function __construct(
        public string $folderSlugId,
        public bool $placed,
        public ?string $reason,
        public string $actorName,
        public string $actorSlugId,
    ) {
    }
}
