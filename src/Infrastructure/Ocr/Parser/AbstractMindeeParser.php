<?php

declare(strict_types=1);

namespace App\Infrastructure\Ocr\Parser;

use App\Domain\Compliance\Enum\DocumentType;
use Mindee\ClientV2;

abstract class AbstractMindeeParser
{
    public function __construct(
        protected readonly ClientV2 $client,
    ) {
    }

    abstract public function supports(DocumentType $type): bool;

    /**
     * @return array<string, mixed>
     */
    public function parse(string $filePath): array
    {
        // La seule sécurité vitale pour Docker : le chemin absolu
        $absolutePath = realpath($filePath) ?: $filePath;

        $prediction = $this->callApi($absolutePath);

        return $this->formatData($prediction);
    }

    abstract protected function callApi(string $absolutePath): object;

    /**
     * @return array<string, mixed>
     */
    abstract protected function formatData(object $prediction): array;
}
