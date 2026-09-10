<?php

declare(strict_types=1);

namespace App\Application\User\DTO\Request;

final readonly class UpdateAdminAccountInput
{
    public function __construct(
        public string $slugId,
        public string $firstName,
        public string $lastName,
        public ?string $phoneNumber,
        /** Valeur d'un App\Domain\User\Enum\AdminRole. */
        public string $role,
    ) {
    }
}
