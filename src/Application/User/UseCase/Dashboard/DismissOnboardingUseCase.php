<?php

namespace App\Application\User\UseCase\Dashboard;

use App\Domain\User\Repository\UserRepositoryInterface;
use Webmozart\Assert\Assert;

final readonly class DismissOnboardingUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(string $userId): void
    {
        $user = $this->userRepository->findBySlug($userId);
        Assert::notNull($user);
        $user->setDismissOnboarding(true);
        $this->userRepository->save($user);
    }
}
