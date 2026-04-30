<?php

namespace App\Application\User\UseCase;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;

readonly class UpdateStripeCustomerIdUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(User $user, string $customerId): void
    {
        $user->stripeCustomerId = $customerId;
        $this->userRepository->save($user);
    }
}
