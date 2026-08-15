<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Request\UpdateWorkspaceRequest;
use App\Domain\Workspace\Event\WorkspaceUpdatedEvent;
use App\Domain\Workspace\Exception\WorkspaceNameAlreadyExistsException;
use App\Domain\Workspace\Exception\WorkspaceSirenAlreadyExistsException;
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

        // 1. On capture les VRAIES anciennes valeurs depuis l'entité
        $oldName = $workspace->name;
        $oldSiren = $workspace->siren;
        $oldSiret = $workspace->siret;

        $newSiren = '' !== $request->siren ? $request->siren : null;
        $newSiret = '' !== $request->siret ? $request->siret : null;

        // 3. Vérification du nom UNIQUEMENT s'il a changé
        $nameHasChanged = $request->name !== $oldName;
        if ($nameHasChanged && $this->workspaceRepository->existsByName($request->name)) {
            throw WorkspaceNameAlreadyExistsException::forName($request->name);
        }

        // 4. Vérification du SIRET UNIQUEMENT s'il a changé
        $siretHasChanged = $newSiret !== $oldSiret;
        if ($siretHasChanged && null !== $newSiret && $this->workspaceRepository->existsBySiret($newSiret)) {
            throw WorkspaceSiretAlreadyExistsException::forSiret($newSiret);
        }

        // 5. 🛡️ NOUVEAU : Vérification du SIREN UNIQUEMENT s'il a changé
        $sirenHasChanged = $newSiren !== $oldSiren;
        if ($sirenHasChanged && null !== $newSiren && $this->workspaceRepository->existsBySiren($newSiren)) {
            // Assure-toi de créer cette exception de domaine !
            throw WorkspaceSirenAlreadyExistsException::forSiren($newSiren);
        }

        // 6. Détection globale des changements
        $hasAnyChanges = $nameHasChanged
            || $siretHasChanged
            || $sirenHasChanged
            || $request->address !== $workspace->address
            || $request->workspaceIndustry !== $workspace->industry;

        if (!$hasAnyChanges) {
            return;
        }

        $workspace->update(
            name: $request->name,
            address: $request->address,
            industry: $request->workspaceIndustry,
            siret: $newSiret,
            siren: $newSiren,
        );

        $this->workspaceRepository->save($workspace);

        // 7. Dispatch de l'événement avec les vraies anciennes données
        $this->eventDispatcher->dispatch(new WorkspaceUpdatedEvent(
            workspace: $workspace,
            oldName: $oldName,
            oldSiren: $oldSiren,
            email: $user->email,
            fullName: $user->getFullName(),
        ));
    }
}
