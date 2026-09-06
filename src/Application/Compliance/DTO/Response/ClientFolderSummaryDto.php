<?php

declare(strict_types=1);

namespace App\Application\Compliance\DTO\Response;

readonly class ClientFolderSummaryDto
{
    public function __construct(
        public string $slugId,
        public string $reference,
        public string $type, // 'individual' | 'business'
        public string $statusValue,
        public string $statusLabel,
        public string $openedAtFormatted,
        public int $documentCount,
        public int $validatedDocumentCount,
        public bool $hasDer,
        public bool $derAcknowledged,
        public bool $relationshipEnded,
        public ?string $purgeDueAtFormatted,
        public bool $underLegalHold,
    ) {
    }
}
