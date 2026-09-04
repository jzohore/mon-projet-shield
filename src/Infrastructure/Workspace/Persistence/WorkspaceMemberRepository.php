<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Persistence;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Enum\InvitedRole;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Uid\Uuid;

/**
 * @method WorkspaceMember|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkspaceMember|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method WorkspaceMember[]    findAll()
 * @method WorkspaceMember[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
final readonly class WorkspaceMemberRepository implements WorkspaceMemberRepositoryInterface
{
    /** @var EntityRepository<WorkspaceMember> */
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(WorkspaceMember::class);
    }

    public function save(WorkspaceMember $workspaceMember, bool $flush = true): void
    {
        $this->entityManager->persist($workspaceMember);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function findByWorkspaceAndUser(Workspace $workspace, User $user): ?WorkspaceMember
    {
        return $this->repository->findOneBy(['workspace' => $workspace, 'user' => $user]);
    }

    /**
     * @return array<int, WorkspaceMember>
     */
    public function findByWorkspace(string $workspaceId): array
    {
        return $this->repository->findBy(['workspace' => $workspaceId]);
    }

    public function delete(WorkspaceMember $workspaceMember): void
    {
        $this->entityManager->remove($workspaceMember);
        $this->entityManager->flush();
    }

    /**
     * @return array<int, WorkspaceMember>
     */
    public function findByUser(User $user): array
    {
        return $this->repository->findBy(['user' => $user]);
    }

    public function findOneByUser(Uuid $userId): ?WorkspaceMember
    {
        return $this->repository->createQueryBuilder('wm')
            ->select('wm')
            ->leftJoin('wm.user', 'user')
            ->where('user.id = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getMembersList(Workspace $workspace, ?string $search = null): Pagerfanta
    {
        $qb = $this->repository->createQueryBuilder('wm')
            ->select('wm', 'user')
            ->leftJoin('wm.workspace', 'workspace')
            ->leftJoin('wm.user', 'user')
            ->andWhere('wm.workspace = :workspace')
            ->setParameter('workspace', $workspace)
            ->orderBy('user.createdAt', 'DESC');

        if ($search) {
            $qb->andWhere('user.email LIKE :search OR user.firstName LIKE :search OR user.lastName LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return new Pagerfanta(new QueryAdapter($qb));
    }

    public function isUserAdminOfWorkspace(User $user, Workspace $workspace): bool
    {
        $member = $this->repository->findOneBy([
            'user' => $user,
            'workspace' => $workspace,
        ]);

        // Si le membre n'existe pas, il n'est pas admin
        if (null === $member) {
            return false;
        }

        return $member->isAdmin();
    }

    /**
     * @return list<User>
     */
    public function findMembersAdmin(Workspace $workspace): array
    {
        /** @var list<WorkspaceMember> $members */
        $members = $this->repository->createQueryBuilder('wm')
            ->addSelect('u')
            ->join('wm.user', 'u')
            ->where('wm.workspace = :workspace')
            ->andWhere('wm.role = :role')
            ->setParameter('workspace', $workspace)
            ->setParameter('role', InvitedRole::ROLE_WORKSPACE_ADMIN->value)
            ->getQuery()
            ->getResult();

        return array_map(static fn (WorkspaceMember $member): User => $member->user, $members);
    }

    public function getMembersActive(string $workspaceSlugId): array
    {
        return $this->repository->createQueryBuilder('wm')
            ->select('user.email, user.firstName, user.lastName')
            ->leftJoin('wm.workspace', 'workspace')
            ->leftJoin('wm.user', 'user')
            ->andWhere('workspace.slugId = :workspaceSlugid')
            ->andWhere('user.isActif = true')
            ->setParameter('workspaceSlugid', $workspaceSlugId)
            ->orderBy('user.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function isAlreadyMember(Workspace $workspace, string $email): bool
    {
        $count = $this->repository->createQueryBuilder('wm')
            ->select('COUNT(wm.id)')
            ->join('wm.user', 'u')
            ->where('wm.workspace = :workspace')
            ->andWhere('u.email = :email')
            ->setParameter('workspace', $workspace)
            ->setParameter('email', $email)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function findOneByUserSlugAndWorkspace(string $userSlugId, string $workspaceId): ?WorkspaceMember
    {
        return $this->repository->createQueryBuilder('wm')
            ->innerJoin('wm.user', 'u')
            ->andWhere('u.slugId = :userSlug')
            ->andWhere('wm.workspace = :workspaceId')
            ->setParameter('userSlug', $userSlugId)
            ->setParameter('workspaceId', $workspaceId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByWorkspace(Uuid $workspaceId): ?WorkspaceMember
    {
        return $this->repository->createQueryBuilder('wm')
            ->innerJoin('wm.user', 'u')
            ->where('wm.workspace = :workspace_id')
            ->andWhere('u.isOwner = :is_owner')
            ->setParameter('workspace_id', $workspaceId->toBinary())
            ->setParameter('is_owner', true)
            // On retire le setMaxResults(1) : on assume l'unicité stricte du modèle.
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOwnerByWorkspace(Uuid $workspaceId): ?WorkspaceMember
    {
        return $this->repository->createQueryBuilder('wm')
            ->innerJoin('wm.user', 'u')
            ->where('wm.workspace = :workspace_id')
            ->andWhere('u.isOwner = :is_owner')
            ->setParameter('workspace_id', $workspaceId->toBinary())
            ->setParameter('is_owner', true)
            // On retire le setMaxResults(1) : on assume l'unicité stricte du modèle.
            ->getQuery()
            ->getOneOrNullResult();
    }
}
