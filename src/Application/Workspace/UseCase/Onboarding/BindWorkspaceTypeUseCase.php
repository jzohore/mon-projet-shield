<?php

namespace App\Application\Workspace\UseCase\Onboarding;

use App\Domain\Billing\Event\CreateBillingModeEvent;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Event\UserOnboardingCompletedEvent;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Enum\WorkspaceType;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class BindWorkspaceTypeUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(WorkspaceType $type, string $workspaceSlugId, string $userSlugId): void
    {
        $user = $this->userRepository->getBySlug($userSlugId);
        $workspace = $this->workspaceRepository->getBySlug($workspaceSlugId);
        $workspace->addWorkspaceType($type);

        $user->onboardingStatus = OnboardingStatus::PLAN_SETUP;
        $this->workspaceRepository->save($workspace);
        $this->userRepository->save($user);
        $this->eventDispatcher->dispatch(new UserOnboardingCompletedEvent($user));
        $this->eventDispatcher->dispatch(new CreateBillingModeEvent($workspace, $user));
    }
}
