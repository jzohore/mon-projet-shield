<?php

namespace App\Application\User\UseCase;

use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Repository\UserRepositoryInterface;
use Webmozart\Assert\Assert;

final readonly class UpdateOnboardingStatusUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(string $userId, OnboardingStatus $status): void
    {
        $user = $this->userRepository->findBySlug($userId);
        Assert::isInstanceOf($user, User::class);
        $user->onboardingStatus = $status;
        $this->userRepository->save($user);
    }
}
