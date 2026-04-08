<?php

namespace App\Infrastructure\KYC\Message;

readonly class ProcessAndStoreKycDocumentMessage
{
    public function __construct(
        public string $documentId,
        public string     $folderSlugId,
        public string     $localTempPath,
        public string     $mimeType,
        public string     $originalName,
        public ?string    $oldStoragePath = null
    ) {}
}
