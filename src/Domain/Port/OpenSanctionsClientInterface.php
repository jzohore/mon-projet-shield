<?php

namespace App\Domain\Port;

interface OpenSanctionsClientInterface
{
    /**
     * @return array{
     *     alerts: array<int, array<string, mixed>>,
     *     total_matches: int
     * }
     */
    public function search(string $name, string $schema = 'Person'): array;
}
