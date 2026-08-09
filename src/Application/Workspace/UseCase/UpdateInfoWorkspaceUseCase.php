<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Request\UpdateWorkspaceRequest;
use App\Domain\Workspace\Event\WorkspaceUpdatedEvent;
use App\Domain\Workspace\Exception\WorkspaceNameAlreadyExistsException;
use App\Domain\Workspace\Exception\WorkspaceSiretAlreadyExistsException;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class UpdateInfoWorkspaceUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private CurrentWorkspaceProvider $currentWorkspaceProvider,
        private EventDispatcherInterface $eventDispatcher,
        private CurrentUserProvider $currentUserProvider,
    ) {
    }

    public function __invoke(UpdateWorkspaceRequest $request): void
    {
        $workspace = $this->currentWorkspaceProvider->getWorkspace();
        $user = $this->currentUserProvider->getUser();

        // 2. On capture les VRAIES anciennes valeurs depuis l'entité
        $oldName = $workspace->name; // (ou $workspace->name selon ton encapsulation)
        $oldSiren = $workspace->siren;
        $oldSiret = $workspace->siret;

        // 3. Vérification du nom UNIQUEMENT s'il a changé
        $nameHasChanged = $request->name !== $oldName;
        if ($nameHasChanged && $this->workspaceRepository->existsByName($request->name)) {
            throw WorkspaceNameAlreadyExistsException::forName($request->name);
        }

        // 4. Vérification du SIRET UNIQUEMENT s'il a changé
        $siretHasChanged = $request->siret !== $oldSiret;
        if ($siretHasChanged && $this->workspaceRepository->existsBySiret($request->siret)) {
            throw WorkspaceSiretAlreadyExistsException::forSiret($request->siret);
        }

        // 5. Détection globale des changements
        $hasAnyChanges = $nameHasChanged
            || $siretHasChanged
            || $request->siren !== $oldSiren
            || $request->address !== $workspace->address
            || $request->workspaceIndustry !== $workspace->industry;

        if (!$hasAnyChanges) {
            return;
        }
        $workspace->update(
            name: $request->name,
            siret: $request->siret,
            siren: $request->siren,
            address: $request->address,
            industry: $request->workspaceIndustry,
        );

        $this->workspaceRepository->save($workspace);

        // 7. Dispatch de l'événement avec les vraies anciennes données
        $this->eventDispatcher->dispatch(new WorkspaceUpdatedEvent($workspace, $user, $oldName, $oldSiren));
    }
}
