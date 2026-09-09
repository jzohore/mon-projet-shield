<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\WorkspaceMember;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Event\WorkspaceMemberReactivatedEvent;
use App\Domain\Workspace\Event\WorkspaceMemberSuspendedEvent;
use App\Domain\Workspace\Exception\CannotRevokeOwnerException;
use App\Domain\Workspace\Exception\MemberNotFoundException;
use App\Domain\Workspace\Exception\NotWorkspaceAdminException;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Domain\Workspace\Service\WorkspacePermissionChecker;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

/**
 * Suspend (ou réactive) le compte d'un collaborateur du cabinet. Réservé aux
 * administrateurs. La suspension coupe l'accès immédiatement (empreinte de
 * session) et se réversible via une réactivation.
 */
final readonly class SetWorkspaceMemberActiveUseCase
{
    public function __construct(
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private CurrentWorkspaceProvider $currentWorkspaceProvider,
        private CurrentUserProvider $currentUserProvider,
        private WorkspacePermissionChecker $permissionChecker,
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(string $targetUserSlugId, bool $active): void
    {
        $workspace = $this->currentWorkspaceProvider->getWorkspace();
        $actor = $this->currentUserProvider->getUser();
        Assert::notNull($workspace->id);

        if (!$this->permissionChecker->isAdmin($actor, $workspace)) {
            throw NotWorkspaceAdminException::create();
        }

        $member = $this->workspaceMemberRepository->findOneByUserSlugAndWorkspace(
            userSlugId: $targetUserSlugId,
            workspaceId: $workspace->id->toString(),
        );

        if (!$member instanceof WorkspaceMember) {
            throw MemberNotFoundException::withUserSlug($targetUserSlugId);
        }

        if ($member->user->isOwner) {
            throw CannotRevokeOwnerException::withWorkspaceAndEmail();
        }

        Assert::notNull($member->user->id);
        Assert::notNull($actor->id);
        if ($member->user->id->equals($actor->id)) {
            throw new \DomainException('Vous ne pouvez pas suspendre votre propre compte.');
        }

        $target = $member->user;

        if ($active === $target->isActif) {
            return; // idempotent
        }

        if ($active) {
            $target->reactivate();
        } else {
            $target->deactivate();
        }
        $this->userRepository->save($target);

        $this->eventDispatcher->dispatch($active
            ? new WorkspaceMemberReactivatedEvent($target, $workspace, $actor)
            : new WorkspaceMemberSuspendedEvent($target, $workspace, $actor));
    }
}
