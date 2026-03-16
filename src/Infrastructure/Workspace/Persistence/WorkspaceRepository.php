<?php

namespace App\Infrastructure\Workspace\Persistence;

use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 *
 * @extends ServiceEntityRepository<Workspace>
 *
 * @method Workspace|null find($id, $lockMode = null, $lockVersion = null)
 * @method Workspace|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method Workspace[]    findAll()
 * @method Workspace[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
class WorkspaceRepository extends ServiceEntityRepository implements WorkspaceRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workspace::class);
    }

    public function save(Workspace $workspace): void
    {
        $this->getEntityManager()->persist($workspace);
        $this->getEntityManager()->flush();
    }

    public function findOneBySlug(string $slug): ?Workspace
    {
        return $this->findOneBy(['slugId' => $slug]);
    }

    /**
     * @return array<string, Workspace>
     */
    public function findMembersByWorkspaceId(string $workspaceId): array
    {
        return $this->findBy(['slugId' => $workspaceId]);
    }
}
