<?php

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Response\WorkspaceInfoResponse;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use Webmozart\Assert\Assert;

final readonly class GetCurrentWorkspaceInfo
{
    public function __construct(
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository
    ) {}

    public function __invoke(User $user): WorkspaceInfoResponse
    {
        $workspaceMember = $this->workspaceMemberRepository->findOneByUser($user);
        Assert::notNull($workspaceMember, "L'utilisateur n'est membre d'aucun espace de travail.");

        $workspace = $workspaceMember->workspace;
        Assert::notNull($workspace, "L'espace de travail est introuvable.");
        Assert::notNull($workspace->slugId, "L'espace de travail n'a pas de slugId.");
        Assert::notNull($workspace->name, "L'espace de travail n'a pas de nom.");
        return WorkspaceInfoResponse::fromEntity($workspace);
    }
}
