<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Request\EditWorkspaceRequest;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Event\WorkspaceUpdatedEvent;
use App\Domain\Workspace\Exception\WorkspaceNameAlreadyExistsException;
use App\Domain\Workspace\Exception\WorkspaceSiretAlreadyExistsException;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

final readonly class EditWorkspaceUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(Workspace $workspace, EditWorkspaceRequest $request): void
    {
        $oldName = $workspace->name;
        $oldSiret = $workspace->siret;
        $updateByEmail = $request->updatedByEmail;
        $updateByFullName = $request->updatedByFullName;
        // 3. Vérification du nom UNIQUEMENT s'il a changé
        $nameHasChanged = $request->name !== $oldName;
        if ($nameHasChanged && $this->workspaceRepository->existsByName($request->name)) {
            throw WorkspaceNameAlreadyExistsException::forName($request->name);
        }

        // 4. Vérification du SIRET UNIQUEMENT s'il a changé
        $siretHasChanged = $request->siret !== $oldSiret;
        Assert::notNull($request->siret);
        if ($siretHasChanged && $this->workspaceRepository->existsBySiret($request->siret)) {
            throw WorkspaceSiretAlreadyExistsException::forSiret($request->siret);
        }

        // 5. Détection globale des changements
        $hasAnyChanges = $nameHasChanged || $siretHasChanged;

        if (!$hasAnyChanges) {
            return;
        }

        $workspace->updateLegalDetails(
            name: $request->name,
            siret: $request->siret,
        );

        // 2. Persistance
        $this->workspaceRepository->save($workspace);

        $this->eventDispatcher->dispatch(new WorkspaceUpdatedEvent(
            workspace: $workspace,
            oldName: $oldName,
            oldSiren: $oldSiret,
            email: $updateByEmail,
            fullName: $updateByFullName,
        ));
    }
}
