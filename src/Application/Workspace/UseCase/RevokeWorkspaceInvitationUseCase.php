<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Request\WorkspaceInvitationRequest;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Event\WorkspaceInvitationRevokeEvent;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

final readonly class RevokeWorkspaceInvitationUseCase
{
    public function __construct(
        private WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(WorkspaceInvitationRequest $request): void
    {
        Assert::notNull($request->slugId);
        $invitation = $this->workspaceInvitationRepository->findBySlugId($request->slugId);
        Assert::isInstanceOf($invitation, WorkspaceInvitation::class);
        $user = $invitation->owner;
        Assert::isInstanceOf($user, User::class);
        $this->workspaceInvitationRepository->delete($invitation);
        $this->eventDispatcher->dispatch(new WorkspaceInvitationRevokeEvent($invitation, $user));
    }
}
