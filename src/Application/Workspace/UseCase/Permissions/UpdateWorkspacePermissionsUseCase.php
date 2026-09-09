<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\Permissions;

use App\Domain\Workspace\Enum\PermissionMode;
use App\Domain\Workspace\Event\WorkspacePermissionsUpdatedEvent;
use App\Domain\Workspace\Exception\NotWorkspaceAdminException;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class UpdateWorkspacePermissionsUseCase
{
    public function __construct(
        private CurrentWorkspaceProvider $currentWorkspaceProvider,
        private CurrentUserProvider $currentUserProvider,
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private WorkspaceRepositoryInterface $workspaceRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(
        bool $canInvite,
        bool $canManagePortfolio,
        bool $canEditCabinet,
        bool $canArchiveFolder,
        PermissionMode $validationMode,
    ): void {
        $workspace = $this->currentWorkspaceProvider->getWorkspace();
        $user = $this->currentUserProvider->getUser();

        if (!$this->workspaceMemberRepository->isUserAdminOfWorkspace($user, $workspace)) {
            throw NotWorkspaceAdminException::create();
        }

        $workspace->updateCollabPermissions(
            $canInvite,
            $canManagePortfolio,
            $canEditCabinet,
            $canArchiveFolder,
            $validationMode,
        );
        $this->workspaceRepository->save($workspace);

        $this->eventDispatcher->dispatch(new WorkspacePermissionsUpdatedEvent(
            $workspace,
            $user,
            [
                'collab_can_invite' => $canInvite,
                'collab_can_manage_portfolio' => $canManagePortfolio,
                'collab_can_edit_cabinet' => $canEditCabinet,
                'collab_can_archive_folder' => $canArchiveFolder,
                'validation_mode' => $validationMode->value,
            ],
        ));
    }
}
