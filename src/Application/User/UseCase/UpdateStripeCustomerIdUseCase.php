<?php

declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;

readonly class UpdateStripeCustomerIdUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(User $user, string $customerId): void
    {
        $user->profile->updateStripeCustomerId($customerId);
        $this->userRepository->save($user);
    }
}
