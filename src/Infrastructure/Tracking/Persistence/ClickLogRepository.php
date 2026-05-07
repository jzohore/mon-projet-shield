<?php

namespace App\Infrastructure\Tracking\Persistence;

use App\Application\Tracking\DTO\Request\ElementStatDTO;
use App\Domain\Tracking\Entity\ClickLog;
use App\Domain\Tracking\Repository\ClickLogRepositoryInterface;
use Doctrine\DBAL\Exception;
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

    /**
     * @return ElementStatDTO[]
     */
    public function getTopClickedElements(int $limit = 10): array
    {
        return $this->repository->createQueryBuilder('c')
            ->select(sprintf(
                'NEW %s(c.elementName, COUNT(c.id))',
                ElementStatDTO::class
            ))
            ->groupBy('c.elementName')
            ->orderBy('COUNT(c.id)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $days
     * @return array<string, int>
     * @throws \DateMalformedStringException
     * @throws Exception
     */
    public function getClicksTrend(int $days = 7): array
    {
        $conn = $this->entityManager->getConnection();

        // 🛡️ FIX 1 : PHP calcule la date de manière déterministe (Clean Code)
        // On recule de X jours et on fixe l'heure à 00:00:00
        $thresholdDate = (new \DateTimeImmutable(sprintf('-%d days', $days)))
            ->setTime(0, 0, 0)
            ->format('Y-m-d H:i:s');

        // 🛡️ FIX 2 : Utilisation de SQL ANSI (Compatible PostgreSQL & MySQL)
        // On remplace DATE() par CAST(... AS DATE) qui est le standard universel.
        // On renomme l'alias 'date' en 'date_val' car 'date' est un mot réservé dans certains contextes PGSQL.
        $sql = '
            SELECT CAST(created_at AS DATE) as date_val, COUNT(id) as total 
            FROM click_logs 
            WHERE created_at >= :thresholdDate
            GROUP BY CAST(created_at AS DATE)
            ORDER BY CAST(created_at AS DATE) ASC
        ';

        $results = $conn->fetchAllAssociative($sql, [
            'thresholdDate' => $thresholdDate,
        ]);

        $trends = [];
        foreach ($results as $row) {
            // PostgreSQL va formater la date en 'YYYY-MM-DD' suite au CAST
            $trends[$row['date_val']] = (int) $row['total'];
        }

        return $trends;
    }
}
