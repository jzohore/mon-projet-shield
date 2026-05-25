<?php

declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;

readonly class UpdateOnboardingStatusUseCase
{
    public function __construct(
        private CurrentUserProvider $currentUserProvider,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(OnboardingStatus $status): void
    {
        $user = $this->currentUserProvider->getUser();
        $user->updateOnboardStatus($status);
        $this->userRepository->save($user);
    }
}
