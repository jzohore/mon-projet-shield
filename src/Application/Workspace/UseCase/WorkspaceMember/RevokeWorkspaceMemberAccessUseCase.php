<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\WorkspaceMember;

use App\Domain\Workspace\Event\WorkspaceMemberRevokedEvent;
use App\Domain\Workspace\Exception\CannotRevokeOwnerException;
use App\Domain\Workspace\Exception\MemberNotFoundException;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

readonly class RevokeWorkspaceMemberAccessUseCase
{
    public function __construct(
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private CurrentWorkspaceProvider $currentWorkspaceProvider,
        private CurrentUserProvider $currentUserProvider,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(string $targetUserSlugId): void
    {
        $workspace = $this->currentWorkspaceProvider->getWorkspace();
        Assert::notNull($workspace->id);

        $actor = $this->currentUserProvider->getUser();
        Assert::notNull($actor->id);

        $member = $this->workspaceMemberRepository->findOneByUserSlugAndWorkspace(
            userSlugId: $targetUserSlugId,
            workspaceId: $workspace->id->toString(),
        );

        if (!$member instanceof \App\Domain\Workspace\Entity\WorkspaceMember) {
            throw MemberNotFoundException::withUserSlug($targetUserSlugId);
        }

        if ($member->user->isOwner) {
            throw CannotRevokeOwnerException::withWorkspaceAndEmail();
        }

        Assert::notNull($member->user->id);

        if ($member->user->id->equals($actor->id)) {
            throw new \DomainException('Vous ne pouvez pas révoquer votre propre accès depuis cette interface.');
        }

        // On garde l'utilisateur en mémoire avant la suppression de la relation
        $revokedUser = $member->user;

        $this->workspaceMemberRepository->delete($member);

        // 🪄 On déclenche l'événement !
        $this->eventDispatcher->dispatch(new WorkspaceMemberRevokedEvent(
            revokedUser: $revokedUser,
            workspace: $workspace,
            actor: $actor
        ));
    }
}
