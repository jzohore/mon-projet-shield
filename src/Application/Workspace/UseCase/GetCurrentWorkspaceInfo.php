<?php

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Response\WorkspaceInfoResponse;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

final readonly class GetCurrentWorkspaceInfo
{
    public function __construct(
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository
    ) {}

    public function __invoke(Uuid $userSlugId): WorkspaceInfoResponse
    {
        $workspaceMember = $this->workspaceMemberRepository->findOneByUser($userSlugId);
        Assert::notNull($workspaceMember, "L'utilisateur n'est membre d'aucun espace de travail.");

        $workspace = $workspaceMember->workspace;
        Assert::notNull($workspace, "L'espace de travail est introuvable.");
        Assert::notNull($workspace->slugId, "L'espace de travail n'a pas de slugId.");
        Assert::notNull($workspace->name, "L'espace de travail n'a pas de nom.");
        return WorkspaceInfoResponse::fromEntity($workspace);
    }
}
