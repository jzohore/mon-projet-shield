<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Persistence;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Exception\WorkspaceNotFoundException;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Uid\Uuid;

/**
 * @method Workspace|null find($id, $lockMode = null, $lockVersion = null)
 * @method Workspace|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method Workspace[]    findAll()
 * @method Workspace[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
final readonly class WorkspaceRepository implements WorkspaceRepositoryInterface
{
    /** @var EntityRepository<Workspace> */
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Workspace::class);
    }

    public function save(Workspace $workspace): void
    {
        $this->entityManager->persist($workspace);
        $this->entityManager->flush();
    }

    public function findOneBySlug(string $slug): ?Workspace
    {
        return $this->repository->findOneBy(['slugId' => $slug]);
    }

    /**
     * @return Workspace[]|User[]
     */
    public function findMembersByWorkspaceId(string $workspaceId): array
    {
        return $this->repository->findBy(['slugId' => $workspaceId]);
    }

    public function findOneByName(?string $name): ?Workspace
    {
        return $this->repository->findOneBy(['name' => $name]);
    }

    public function getBySlug(string $slug): Workspace
    {
        $workspace = $this->repository->findOneBy(['slugId' => $slug]);

        if (null === $workspace) {
            throw WorkspaceNotFoundException::withSlug($slug);
        }

        return $workspace;
    }

    public function getById(Uuid $id): Workspace
    {
        $workspace = $this->repository->findOneBy(['id' => $id]);

        if (null === $workspace) {
            throw WorkspaceNotFoundException::withId((string) $id);
        }

        return $workspace;
    }

    public function getReference(Uuid $id): Workspace
    {
        $workspace = $this->entityManager->getReference(Workspace::class, $id);

        if (null === $workspace) {
            throw WorkspaceNotFoundException::withId((string) $id);
        }

        return $workspace;
    }

    public function countAll(): int
    {
        return (int) $this->repository->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActive(): int
    {
        return (int) $this->repository->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            // Adapte cette condition selon ton entité (ex: status, isTrial, etc.)
            ->where('w.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Workspace[]
     */
    public function findLatest(int $limit = 5): array
    {
        return $this->repository->createQueryBuilder('w')
            ->orderBy('w.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function existsByName(string $name): bool
    {
        $nameCount = $this->repository->count(['name' => $name]);

        return $nameCount > 0;
    }

    public function existsBySiret(string $siret): bool
    {
        $siretCount = $this->repository->count(['siret' => $siret]);

        return $siretCount > 0;
    }

    /**
     * @return Workspace[]
     */
    public function findActiveWithSiret(): array
    {
        return $this->repository->createQueryBuilder('w')
            ->select('w')
            // Adapte cette condition selon ton entité (ex: status, isTrial, etc.)
            ->where('w.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getresult();
    }

    /**
     * @return Pagerfanta<Workspace>
     */
    public function getPaginatedWorkspaces(int $page, int $maxPerPage = 10, ?string $search = null): Pagerfanta
    {
        $qb = $this->repository->createQueryBuilder('w')
            ->orderBy('w.createdAt', 'DESC');

        if (null !== $search && '' !== trim($search)) {
            $qb->andWhere('w.name LIKE :search OR w.siret LIKE :search OR w.legalName LIKE :search')
                ->setParameter('search', '%' . trim($search) . '%');
        }

        $adapter = new QueryAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);

        $pagerfanta->setMaxPerPage($maxPerPage);
        $pagerfanta->setCurrentPage(max(1, $page));

        return $pagerfanta;
    }
}
