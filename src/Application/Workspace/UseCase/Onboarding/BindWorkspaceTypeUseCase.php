<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\Onboarding;

use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Enum\WorkspaceType;
use App\Domain\Workspace\Event\WorkspacePlanSelectedEvent;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class BindWorkspaceTypeUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(WorkspaceType $workspaceType, User $user, Workspace $workspace): void
    {
        $workspace->addWorkspaceType($workspaceType);
        $workspace->markAsActive();

        $user->updateOnboardStatus(OnboardingStatus::PLAN_SETUP);
        $user->enabledProfil();
        $this->transactionManager->transactional(function () use ($workspace, $user): void {
            $this->workspaceRepository->save($workspace);
            $this->userRepository->save($user);
        });

        // ⚠️ L'onboarding n'est PAS terminé ici : on notifie seulement que le type
        // de compte est choisi, pour préparer Stripe/facturation en tâche de fond.
        // L'événement de fin d'onboarding est émis par MarkAsOnboardingCompletedUseCase.
        $this->eventDispatcher->dispatch(new WorkspacePlanSelectedEvent($user, $workspace));
    }
}
