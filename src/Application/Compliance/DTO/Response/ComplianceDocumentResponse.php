<?php

declare(strict_types=1);

namespace App\Application\Compliance\DTO\Response;

readonly class ComplianceDocumentResponse
{
    /**
     * @param array<string, mixed>|null $ocrData
     */
    public function __construct(
        public string $id,
        public string $typeLabel,
        public string $statusValue,
        public bool $isAskToClient,
        public ?string $storagePath,
        public ?string $rejectionReason,
        public ?array $ocrData, // Les données de la macro Twig
        public ?string $stakeholderSlug,
        public ?string $filename,
        public ?string $mimeType,
        public ?int $size,
    ) {
    }
}
