<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Response\WorkspaceDetailsDto;
use App\Domain\Workspace\Exception\WorkspaceNotFoundException;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;

final readonly class GetWorkspaceDetailsUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
    ) {
    }

    public function __invoke(string $slugId): WorkspaceDetailsDto
    {
        // On récupère le workspace. Le repository doit gérer l'optimisation des requêtes
        // (par exemple, faire des left joins sur les collections si on ne veut pas de lazy loading,
        // ou s'assurer que les collections sont en EXTRA_LAZY pour les count()).
        $workspace = $this->workspaceRepository->findOneBySlug($slugId);

        if (!$workspace instanceof \App\Domain\Workspace\Entity\Workspace) {
            throw WorkspaceNotFoundException::withSlug($slugId);
        }

        return WorkspaceDetailsDto::fromEntity($workspace);
    }
}
