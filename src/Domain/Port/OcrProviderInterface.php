<?php

namespace App\Domain\Port;

use App\Domain\Kyc\Enum\DocumentType;

interface OcrProviderInterface
{
    /**
     * @param string $filePath Le chemin local (temporaire) du document
     * @return array<string, mixed> Les données extraites normalisées
     * @throws \DomainException Si le document n'est pas supporté ou illisible
     */
    public function extractData(DocumentType $type, string $filePath): array;
}
