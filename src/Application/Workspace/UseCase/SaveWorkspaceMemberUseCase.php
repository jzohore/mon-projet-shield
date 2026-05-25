<?php

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Request\WorkspaceMemberRequest;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Enum\InvitedRole;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Exception;
use Symfony\Component\Uid\Uuid;

readonly class SaveWorkspaceMemberUseCase
{
    /**
     * @param WorkspaceMemberRepositoryInterface $workspaceMemberRepository
     * @param WorkspaceRepositoryInterface $workspaceRepository
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private WorkspaceRepositoryInterface $workspaceRepository,
        private UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @param WorkspaceMemberRequest $request
     * @return void
     * @throws Exception
     */
    public function __invoke(WorkspaceMemberRequest $request): void
    {
        $workspaceUuid = Uuid::fromString($request->workspaceId);
        $workspace = $this->workspaceRepository->getReference($workspaceUuid);

        $userUuid = Uuid::fromString($request->userId);
        $user = $this->userRepository->getReference($userUuid);

        $workspaceMember = WorkspaceMember::create($workspace, $user, InvitedRole::ROLE_WORKSPACE_ADMIN);

        $this->workspaceMemberRepository->save($workspaceMember);
    }
}
