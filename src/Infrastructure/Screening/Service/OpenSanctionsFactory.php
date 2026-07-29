<?php

declare(strict_types=1);

namespace App\Infrastructure\Screening\Service;

use App\Domain\Port\OpenSanctionsClientInterface;

readonly class OpenSanctionsFactory
{
    public function __construct(
        private FakeOpenSanctionsClient $fakeOpenSanctionsClient,
        private OpenSanctionsClient $openSanctionsClient,
    ) {
    }

    public function create(string $mode): OpenSanctionsClientInterface
    {
        return match ($mode) {
            'real' => $this->openSanctionsClient,
            default => $this->fakeOpenSanctionsClient,
        };
    }
}
