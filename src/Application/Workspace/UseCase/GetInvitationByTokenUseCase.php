<?php

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Response\WorkspaceInvitationInfoResponse;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use Webmozart\Assert\Assert;

readonly class GetInvitationByTokenUseCase
{
    public function __construct(
        private WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
    ) {}

    public function __invoke(string $shareToken): WorkspaceInvitationInfoResponse
    {
        Assert::notNull($shareToken);

        $invitation = $this->workspaceInvitationRepository->findByToken($shareToken);

        if ($invitation === null || !$invitation->isMagicLinkTokenValid()) {
            throw new \DomainException('Invitation introuvable ou expirée.');
        }

        return WorkspaceInvitationInfoResponse::fromEntity($invitation);
    }
}
