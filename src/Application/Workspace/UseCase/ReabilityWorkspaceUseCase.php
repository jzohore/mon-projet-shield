<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Request\CreateWorkspaceRequest;
use App\Application\Workspace\DTO\Response\WorkspaceInfoResponse;
use App\Domain\Workspace\Exception\WorkspaceNameAlreadyExistsException;
use App\Domain\Workspace\Exception\WorkspaceSiretAlreadyExistsException;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;

readonly class ReabilityWorkspaceUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private CurrentWorkspaceProvider $currentWorkspaceProvider,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(CreateWorkspaceRequest $request): WorkspaceInfoResponse
    {
        if ($this->workspaceRepository->existsByName($request->name)) {
            throw WorkspaceNameAlreadyExistsException::forName($request->name);
        }

        if ($request->siret && $this->workspaceRepository->existsBySiret($request->siret)) {
            throw WorkspaceSiretAlreadyExistsException::forSiret($request->siret);
        }

        $workspace = $this->currentWorkspaceProvider->getWorkspace();

        $workspace->changeLegalEntity(
            newSiret: $request->siret,
            newSiren: $request->siren,
            newName: $request->name,
            newAddress: $request->address,
            etatAdministratif: $request->etatAdministratif,
        );

        $this->workspaceRepository->save($workspace);

        return WorkspaceInfoResponse::fromEntity($workspace);
    }
}
