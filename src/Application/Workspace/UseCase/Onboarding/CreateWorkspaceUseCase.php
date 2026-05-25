<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\Onboarding;

use App\Application\Workspace\DTO\Request\CreateWorkspaceRequest;
use App\Application\Workspace\DTO\Response\WorkspaceInfoResponse;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Enum\InvitedRole;
use App\Domain\Workspace\Event\WorkspaceCreatedEvent;
use App\Domain\Workspace\Exception\WorkspaceNameAlreadyExistsException;
use App\Domain\Workspace\Exception\WorkspaceSiretAlreadyExistsException;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class CreateWorkspaceUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
        private TransactionManagerInterface $transactionManager,
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private CurrentUserProvider $currentUserProvider,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(CreateWorkspaceRequest $request): WorkspaceInfoResponse
    {
        $user = $this->currentUserProvider->getUser();

        if ($this->workspaceRepository->existsByName($request->name)) {
            throw WorkspaceNameAlreadyExistsException::forName($request->name);
        }

        if ($request->siret && $this->workspaceRepository->existsBySiret($request->siret)) {
            throw WorkspaceSiretAlreadyExistsException::forSiret($request->siret);
        }

        $workspace = Workspace::create(
            name: $request->name,
            siret: $request->siret,
            legalName: $request->legalName,
            address: $request->address,
            industry: $request->workspaceIndustry,
        );

        $user->updateOnboardStatus(OnboardingStatus::WORKSPACE_SETUP);

        $workspaceMember = WorkspaceMember::create($workspace, $user, InvitedRole::ROLE_WORKSPACE_ADMIN);

        $this->transactionManager->transactional(function () use ($workspace, $user, $workspaceMember): void {
            $this->workspaceRepository->save($workspace);
            $this->userRepository->save($user);
            $this->workspaceMemberRepository->save($workspaceMember);
        });

        $this->eventDispatcher->dispatch(new WorkspaceCreatedEvent($workspace, $user));

        return WorkSpaceInfoResponse::fromEntity($workspace);
    }
}
