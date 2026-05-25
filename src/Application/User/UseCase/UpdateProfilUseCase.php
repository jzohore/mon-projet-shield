<?php

declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;

final readonly class UpdateProfilUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(User $user): void
    {
        $user->enabledProfil();
        $this->userRepository->save($user);
    }
}
