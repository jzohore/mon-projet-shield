<?php

namespace App\Infrastructure\Ocr;

use App\Domain\Port\OcrProviderInterface;

readonly class OcrFactory
{
    public function __construct(
        private MindeeProvider  $mindeeProvider,
        private FakeOcrProvider $fakeOcrProvider
    ) {}

    public function create(string $mode): OcrProviderInterface
    {
        return match ($mode) {
            'real' => $this->mindeeProvider,
            default => $this->fakeOcrProvider,
        };
    }
}
