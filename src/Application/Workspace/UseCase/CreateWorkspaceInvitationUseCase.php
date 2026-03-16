<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Request\CreateWorkspaceInvitationRequest;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Event\WorkspaceInvitationCreatedEvent;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

final readonly class CreateWorkspaceInvitationUseCase
{
    public function __construct(
        private WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(CreateWorkspaceInvitationRequest $request): void
    {
        Assert::notNull($request->email);
        $user = $this->userRepository->findBySlug($request->userSlugId);
        Assert::isInstanceOf($user, User::class);
        $workspace = $user->workspace;
        Assert::isInstanceOf($workspace, Workspace::class);
        $newInvitation = new WorkspaceInvitation(
            owner: $user,
            workspace: $workspace,
            email: $request->email,
            invitedRole: $request->invitedRole
        );

        $this->workspaceInvitationRepository->save($newInvitation);
        $this->eventDispatcher->dispatch(new WorkspaceInvitationCreatedEvent(
            workspaceInvitation: $newInvitation
        ));
    }
}
