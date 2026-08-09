<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\WorkspaceMember;

use App\Application\Workspace\DTO\Response\WorkspaceMemberDetailsResponse;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Exception\MemberNotFoundException;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Webmozart\Assert\Assert;

readonly class GetWorkspaceMemberDetailsUseCase
{
    public function __construct(
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private CurrentWorkspaceProvider $currentWorkspaceProvider,
    ) {
    }

    public function __invoke(string $targetUserSlugId, User $currentUser): WorkspaceMemberDetailsResponse
    {
        $workspace = $this->currentWorkspaceProvider->getWorkspace();
        Assert::notNull($workspace->id);

        $member = $this->workspaceMemberRepository->findOneByUserSlugAndWorkspace(
            userSlugId: $targetUserSlugId,
            workspaceId: $workspace->id->toString(),
        );

        if (!$member instanceof \App\Domain\Workspace\Entity\WorkspaceMember) {
            throw MemberNotFoundException::withUserSlug($targetUserSlugId);
        }

        return WorkspaceMemberDetailsResponse::fromEntity($member);
    }
}
