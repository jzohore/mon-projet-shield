<?php

declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use Webmozart\Assert\Assert;

readonly class MarkAsOnboardingCompletedUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(User $user): void
    {
        Assert::notNull($user);
        $user->markAsOnboardingCompleted();
        $this->userRepository->save($user);
    }
}
