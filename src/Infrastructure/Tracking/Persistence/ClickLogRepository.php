<?php

namespace App\Infrastructure\Tracking\Persistence;

use App\Domain\Tracking\Entity\ClickLog;
use App\Domain\Tracking\Repository\ClickLogRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/**
 * @method ClickLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClickLog|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method ClickLog[]    findAll()
 * @method ClickLog[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
class ClickLogRepository implements ClickLogRepositoryInterface
{
    /** @var EntityRepository<ClickLog> */
    private EntityRepository $repository;
    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(ClickLog::class);
    }

    public function save(ClickLog $clickLog, bool $flush = false): void
    {
        $this->entityManager->persist($clickLog);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function findBySlug(string $slugId): ?ClickLog
    {
        return $this->findOneBy(['slugId' => $slugId]);
    }

    public function getStatsByElement(\DateTimeImmutable $since): array
    {
        $results = $this->repository->createQueryBuilder('c')
            ->select('c.elementName, COUNT(c.id) as count')
            ->where('c.createdAt >= :since')
            ->setParameter('since', $since)
            ->groupBy('c.elementName')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($results as $row) {
            $stats[$row['elementName']] = (int) $row['count'];
        }

        return $stats;
    }

    public function countBySource(string $source): int
    {
        return (int) $this->repository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.utmSource = :source')
            ->setParameter('source', $source)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function remove(ClickLog $clickLog, bool $flush = false): void
    {
        $this->entityManager->remove($clickLog);

        if ($flush) {
            $this->entityManager->flush();
        }
    }
}
