<?php

declare(strict_types=1);

namespace App\Infrastructure\Ocr;

use App\Domain\Port\OcrProviderInterface;

readonly class OcrFactory
{
    public function __construct(
        private MindeeProvider $mindeeProvider,
        private FakeOcrProvider $fakeOcrProvider,
        private AmazonTextractProvider $amazonTextractProvider,
    ) {
    }

    public function create(string $mode): OcrProviderInterface
    {
        return match ($mode) {
            'real' => $this->amazonTextractProvider,
            'mindee' => $this->mindeeProvider,
            default => $this->fakeOcrProvider,
        };
    }
}
