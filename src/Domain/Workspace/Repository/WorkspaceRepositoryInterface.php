<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Repository;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Component\Uid\Uuid;

interface WorkspaceRepositoryInterface
{
    /**
     * @param Workspace $workspace
     * @return void
     */
    public function save(Workspace $workspace): void;

    /**
     * @param string $slug
     * @return Workspace|null
     */
    public function findOneBySlug(string $slug): ?Workspace;

    /**
     * @return Workspace[]|User[]
     */
    public function findMembersByWorkspaceId(string $workspaceId): array;

    /**
     * @param string|null $name
     * @return Workspace|null
     */
    public function findOneByName(?string $name): ?Workspace;

    /**
     * @param string $slug
     * @return Workspace
     */
    public function getBySlug(string $slug): Workspace;

    /**
     * @param Uuid $id
     * @return Workspace
     */
    public function getById(Uuid $id): Workspace;

    public function countAll(): int;
    public function countActive(): int;

    /**
     * Récupère les derniers Workspaces créés.
     *
     * @param int $limit Le nombre maximum de résultats
     * @return Workspace[]
     */
    public function findLatest(int $limit = 5): array;

}
