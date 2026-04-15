<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Request\CreateWorkspaceInvitationRequest;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Event\WorkspaceInvitationCreatedEvent;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

final readonly class CreateWorkspaceInvitationUseCase
{
    public function __construct(
        private WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(CreateWorkspaceInvitationRequest $request): void
    {
        Assert::notNull($request->email);
        Assert::notNull($request->firstName);
        Assert::notNull($request->lastName);
        $user = $this->userRepository->findBySlug($request->userSlugId);
        Assert::isInstanceOf($user, User::class);
        $userId = $user->id;
        Assert::notNull($userId, "L'utilisateur doit avoir un ID pour récupérer le workspace.");
        $findWorkspace = $this->workspaceMemberRepository->findOneByUser($userId);
        Assert::notNull($findWorkspace);
        $workspace = $findWorkspace->workspace;
        Assert::notNull($workspace);
        $newInvitation = WorkspaceInvitation::create(
            owner: $user,
            workspace: $workspace,
            email: $request->email,
            firstName: $request->firstName,
            lastName: $request->lastName,
            invitedRole: $request->invitedRole,
        );

        $this->workspaceInvitationRepository->save($newInvitation);
        $this->eventDispatcher->dispatch(new WorkspaceInvitationCreatedEvent(
            workspaceInvitation: $newInvitation
        ));
    }
}
