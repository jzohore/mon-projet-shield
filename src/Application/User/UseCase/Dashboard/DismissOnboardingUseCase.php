<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Dashboard;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;

readonly class DismissOnboardingUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private CurrentUserProvider $currentUserProvider,
    ) {
    }

    public function __invoke(): void
    {
        $user = $this->currentUserProvider->getUser();
        $user->dismissOnboarding();
        $this->userRepository->save($user);
    }
}
