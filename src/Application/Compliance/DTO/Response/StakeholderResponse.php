<?php

declare(strict_types=1);

namespace App\Application\Compliance\DTO\Response;

readonly class StakeholderResponse
{
    public function __construct(
        public string $slugId,
        public string $fullName,
        public string $initials,
        public string $roleLabel,
        public float $percentage,
        public bool $isUbo,
    ) {
    }
}
