<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Persistence;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\User\Entity\Client;
use App\Domain\User\Repository\ClientRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Uid\Uuid;

/**
 * @method Client|null find($id, $lockMode = null, $lockVersion = null)
 * @method Client|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method Client[]    findAll()
 * @method Client[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
readonly class ClientRepository implements ClientRepositoryInterface
{
    /** @var EntityRepository<Client> */
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Client::class);
    }

    public function findByEmail(string $email): ?Client
    {
        return $this->repository->findOneBy(['email' => $email]);
    }

    public function findById(Uuid|string $id): ?Client
    {
        return $this->repository->find($id);
    }

    public function save(Client $client, bool $flush = true): void
    {
        $this->entityManager->persist($client);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function remove(Client $client, bool $flush = true): void
    {
        $this->entityManager->remove($client);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function findByMagicLink(string $magicLink): ?Client
    {
        return $this->repository->findOneBy(['magicLinkToken' => $magicLink]);
    }

    public function findAllByWorkspace(Workspace $workspace, ?string $search = null, string $sort = 'recent', ?bool $onlyActive = null): Pagerfanta
    {
        $qb = $this->repository->createQueryBuilder('c')
            ->innerJoin('c.workspaces', 'w')
            ->andWhere('w = :workspace')
            ->setParameter('workspace', $workspace);

        if (null !== $search && '' !== trim($search)) {
            $qb->andWhere('LOWER(c.firstName) LIKE :s OR LOWER(c.lastName) LIKE :s OR LOWER(c.email) LIKE :s')
                ->setParameter('s', '%' . mb_strtolower(trim($search)) . '%');
        }

        if (null !== $onlyActive) {
            $qb->andWhere('c.isActif = :active')->setParameter('active', $onlyActive);
        }

        match ($sort) {
            'name' => $qb->orderBy('c.lastName', 'ASC')->addOrderBy('c.firstName', 'ASC'),
            'status' => $qb->orderBy('c.isActif', 'DESC')->addOrderBy('c.createdAt', 'DESC'),
            default => $qb->orderBy('c.createdAt', 'DESC'),
        };

        return new Pagerfanta(new QueryAdapter($qb));
    }

    public function findOneBySlugIdAndWorkspace(string $slugId, Workspace $workspace): ?Client
    {
        return $this->repository->createQueryBuilder('c')
            ->innerJoin('c.workspaces', 'w')
            ->andWhere('c.slugId = :slugId')
            ->andWhere('w = :workspace')
            ->setParameter('slugId', $slugId)
            ->setParameter('workspace', $workspace)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function folderCountByClients(Workspace $workspace, array $clientIds): array
    {
        if ([] === $clientIds) {
            return [];
        }

        /** @var list<array{cid: string, cnt: int}> $rows */
        $rows = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(f.client) AS cid', 'COUNT(f.id) AS cnt')
            ->from(ComplianceFolder::class, 'f')
            ->andWhere('f.workspace = :workspace')
            ->andWhere('f.client IN (:clientIds)')
            ->andWhere('f.status != :deleted')
            ->setParameter('workspace', $workspace)
            ->setParameter('clientIds', $clientIds)
            ->setParameter('deleted', ComplianceFolderStatus::DELETED)
            ->groupBy('cid')
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['cid']] = (int) $row['cnt'];
        }

        return $map;
    }
}
