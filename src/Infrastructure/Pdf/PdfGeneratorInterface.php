<?php

declare(strict_types=1);

namespace App\Infrastructure\Pdf;

interface PdfGeneratorInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function generateFromHtml(string $template, array $context): string;
}
