<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\Onboarding;

use App\Application\Workspace\DTO\Request\CreateWorkspaceRequest;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\Firm\Entity\RegulatoryProfile;
use App\Domain\Firm\Repository\RegulatoryProfileRepositoryInterface;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Enum\InvitedRole;
use App\Domain\Workspace\Event\WorkspaceCreatedEvent;
use App\Domain\Workspace\Exception\WorkspaceNameAlreadyExistsException;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
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
        private RegulatoryProfileRepositoryInterface $regulatoryProfileRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(CreateWorkspaceRequest $request): void
    {
        $user = $this->currentUserProvider->getUser();

        if ($this->workspaceRepository->existsByName($request->name)) {
            throw WorkspaceNameAlreadyExistsException::forName($request->name);
        }

        $workspace = Workspace::create(
            name: $request->name,
            legalName: $request->legalName,
            address: $request->address,
            etatAdministratif: $request->etatAdministratif,
            industry: $request->workspaceIndustry,
            email: $user->email,
        );

        $user->updateOnboardStatus(OnboardingStatus::WORKSPACE_SETUP);

        $workspaceMember = WorkspaceMember::create($workspace, $user, InvitedRole::ROLE_WORKSPACE_ADMIN);
        $regulatoryProfile = RegulatoryProfile::initiate($workspace);

        $this->transactionManager->transactional(function () use ($workspace, $user, $workspaceMember, $regulatoryProfile): void {
            $this->workspaceRepository->save($workspace);
            $this->userRepository->save($user);
            $this->workspaceMemberRepository->save($workspaceMember);
            $this->regulatoryProfileRepository->save($regulatoryProfile);
        });

        $this->eventDispatcher->dispatch(new WorkspaceCreatedEvent($workspace, $user));
    }
}
