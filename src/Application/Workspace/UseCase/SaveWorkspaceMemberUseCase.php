<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Request\WorkspaceMemberRequest;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Enum\InvitedRole;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\Uid\Uuid;

readonly class SaveWorkspaceMemberUseCase
{
    public function __construct(
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private WorkspaceRepositoryInterface $workspaceRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @throws \Exception
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
