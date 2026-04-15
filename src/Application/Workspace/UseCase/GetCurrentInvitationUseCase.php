<?php

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Response\WorkspaceInvitationInfoResponse;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use Webmozart\Assert\Assert;

final readonly class GetCurrentInvitationUseCase
{
    public function __construct(
        private WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
    ) {}

    public function __invoke(string $slugId): WorkspaceInvitationInfoResponse
    {
        Assert::notNull($slugId);
        $invitation = $this->workspaceInvitationRepository->findBySlugId($slugId);
        Assert::notNull($invitation);
        return WorkspaceInvitationInfoResponse::fromEntity($invitation);
    }
}
