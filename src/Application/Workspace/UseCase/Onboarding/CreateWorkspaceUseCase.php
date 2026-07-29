<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\Onboarding;

use App\Application\Workspace\DTO\Request\CreateWorkspaceRequest;
use App\Application\Workspace\DTO\Response\WorkspaceInfoResponse;
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
use App\Domain\Workspace\Exception\WorkspaceSiretAlreadyExistsException;
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
            siren: $request->siren,
            legalName: $request->legalName,
            address: $request->address,
            etatAdministratif: $request->etatAdministratif,
            industry: $request->workspaceIndustry,
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

        return WorkspaceInfoResponse::fromEntity($workspace, $regulatoryProfile->filename, $regulatoryProfile->id?->toString());
    }
}
