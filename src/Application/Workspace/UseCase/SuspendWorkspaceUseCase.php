<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Request\SuspendWorkspaceRequest;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Event\WorkspaceSuspendedEvent;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

final readonly class SuspendWorkspaceUseCase
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private WorkspaceRepositoryInterface $workspaceRepository,
    ) {
    }

    public function __invoke(Workspace $workspace, SuspendWorkspaceRequest $request): void
    {
        Assert::notNull($request->suspensionReason);
        $workspace->suspend($request->suspensionReason);

        // 2. Persistance
        $this->workspaceRepository->save($workspace);

        $this->eventDispatcher->dispatch(new WorkspaceSuspendedEvent(
            workspace: $workspace,
            email: $request->deletedByEmail,
            fullName: $request->deletedByFullName,
        ));
    }
}
