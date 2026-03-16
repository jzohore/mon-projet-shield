<?php

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Request\CreateWorkspaceRequest;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Event\WorkspaceCreatedEvent;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

final readonly class CreateWorkspaceUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(CreateWorkspaceRequest $request): void
    {
        Assert::notNull($request->name);
        Assert::notNull($request->userSlugId, 'Le slug utilisateur est requis.');
        $user = $this->userRepository->findBySlug($request->userSlugId);

        Assert::isInstanceOf($user, User::class, sprintf('Aucun utilisateur trouvé pour le slug "%s"', $request->userSlugId));

        $workspace = new Workspace(
            $request->name,
        );
        $workspace->addMember($user);
        $user->onboardingStatus = OnboardingStatus::WORKSPACE_SETUP;
        $this->workspaceRepository->save($workspace);
        $this->userRepository->save($user);

        $this->eventDispatcher->dispatch(new WorkspaceCreatedEvent($workspace, $user));
    }
}
