<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Request\CreateWorkspaceRequest;
use App\Application\Workspace\DTO\Response\WorkspaceInfoResponse;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Event\WorkspaceCreatedEvent;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class CreateWorkspaceUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(CreateWorkspaceRequest $request): WorkspaceInfoResponse
    {
        $user = $this->userRepository->getBySlug($request->userSlugId);

        $workspace = Workspace::create(
            name: $request->name,
            siret: $request->siret,
            legalName: $request->legalName,
            address: $request->address,
            industry: $request->workspaceIndustry,
        );

        $user->onboardingStatus = OnboardingStatus::WORKSPACE_SETUP;
        $this->workspaceRepository->save($workspace);
        $this->userRepository->save($user);

        $this->eventDispatcher->dispatch(new WorkspaceCreatedEvent($workspace, $user));

        return WorkSpaceInfoResponse::fromEntity($workspace);
    }
}
