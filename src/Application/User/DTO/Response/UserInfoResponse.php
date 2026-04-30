<?php

namespace App\Application\User\DTO\Response;

use App\Domain\User\Entity\User;

final readonly class UserInfoResponse
{
    public function __construct(
        public string $email,
        public string $slugId,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $customerStripeId = null
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            email: $user->email ?? '',
            slugId: $user->slugId ?? '',
            firstName: $user->firstName,
            lastName: $user->lastName,
        );
    }
}
