<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Repository;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Uid\Uuid;

interface WorkspaceRepositoryInterface
{
    public function save(Workspace $workspace): void;

    public function findOneBySlug(string $slug): ?Workspace;

    /**
     * @return Workspace[]|User[]
     */
    public function findMembersByWorkspaceId(string $workspaceId): array;

    public function findOneByName(?string $name): ?Workspace;

    public function getBySlug(string $slug): Workspace;

    public function getById(Uuid $id): Workspace;

    public function countAll(): int;

    public function countActive(): int;

    /**
     * Récupère les derniers Workspaces créés.
     *
     * @param int $limit Le nombre maximum de résultats
     *
     * @return Workspace[]
     */
    public function findLatest(int $limit = 5): array;

    public function getReference(Uuid $id): Workspace;

    public function existsByName(string $name): bool;

    public function existsBySiret(string $siret): bool;

    /**
     * @return Workspace[]
     */
    public function findActiveWithSiret(): array;

    /**
     * @return Pagerfanta<Workspace>
     */
    public function getPaginatedWorkspaces(int $page, int $maxPerPage = 10, ?string $search = null): Pagerfanta;
}
