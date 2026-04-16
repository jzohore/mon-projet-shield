<?php

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Request\WorkspaceMemberRequest;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Webmozart\Assert\Assert;

readonly class SaveWorkspaceMemberUseCase
{
    public function __construct(
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private WorkspaceRepositoryInterface $workspaceRepository,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(WorkspaceMemberRequest $request): void
    {
        Assert::notNull($request->workspaceSlugId);
        Assert::notNull($request->userSlugId);
        Assert::notNull($request->role);

        $workspace = $this->workspaceRepository->findOneBySlug($request->workspaceSlugId);
        Assert::isInstanceOf($workspace, Workspace::class);
        $user = $this->userRepository->findBySlug($request->userSlugId);
        Assert::isInstanceOf($user, User::class);
        $role = $request->role;
        $workspaceMember = WorkspaceMember::create($workspace, $user, $role);

        $this->workspaceMemberRepository->save($workspaceMember);
    }
}
