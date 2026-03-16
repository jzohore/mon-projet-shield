<?php

namespace App\Infrastructure\Workspace\Persistence;

use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 *
 * @extends ServiceEntityRepository<WorkspaceInvitation>
 *
 * @method WorkspaceInvitation|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkspaceInvitation|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method WorkspaceInvitation[]    findAll()
 * @method WorkspaceInvitation[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
class WorkspaceInvitationRepository extends ServiceEntityRepository implements WorkspaceInvitationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkspaceInvitation::class);
    }

    public function save(WorkspaceInvitation $workspaceInvitation): void
    {
        $this->getEntityManager()->persist($workspaceInvitation);
        $this->getEntityManager()->flush();
    }

    /**
     * @return array<int, WorkspaceInvitation>
     */
    public function findByWorkspace(Workspace $workspace): array
    {
        return $this->findBy(['workspace' => $workspace], ['createdAt' => 'DESC']);
    }

    public function findByEmail(string $email): ?WorkspaceInvitation
    {
        return $this->findOneBy(['email' => $email]);
    }
}
