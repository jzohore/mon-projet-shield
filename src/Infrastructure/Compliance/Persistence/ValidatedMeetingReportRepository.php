<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Persistence;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Entity\ValidatedMeetingReport;
use App\Domain\Compliance\Repository\ValidatedMeetingReportRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

/**
 * @method ValidatedMeetingReport|null find($id, $lockMode = null, $lockVersion = null)
 * @method ValidatedMeetingReport|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method ValidatedMeetingReport[]    findAll()
 * @method ValidatedMeetingReport[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
readonly class ValidatedMeetingReportRepository implements ValidatedMeetingReportRepositoryInterface
{
    /** @var EntityRepository<ValidatedMeetingReport> */
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(ValidatedMeetingReport::class);
    }

    public function save(ValidatedMeetingReport $report, bool $flush = true): void
    {
        $this->entityManager->persist($report);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function findById(Uuid|string $id): ?ValidatedMeetingReport
    {
        return $this->repository->find($id);
    }

    public function findBySlugId(string $slugId): ?ValidatedMeetingReport
    {
        return $this->repository->findOneBy(['slugId' => $slugId]);
    }

    public function findInForceByFolder(ComplianceFolder $complianceFolder): ?ValidatedMeetingReport
    {
        return $this->repository->createQueryBuilder('r')
            ->andWhere('r.complianceFolder = :folder')
            ->andWhere('r.revokedAt IS NULL')
            ->orderBy('r.version', 'DESC')
            ->setParameter('folder', $complianceFolder)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLatestVersionNumber(ComplianceFolder $complianceFolder): int
    {
        $max = $this->repository->createQueryBuilder('r')
            ->select('MAX(r.version)')
            ->andWhere('r.complianceFolder = :folder')
            ->setParameter('folder', $complianceFolder)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $max;
    }

    public function findAllByFolder(ComplianceFolder $complianceFolder): array
    {
        return $this->repository->createQueryBuilder('r')
            ->andWhere('r.complianceFolder = :folder')
            ->orderBy('r.version', 'DESC')
            ->setParameter('folder', $complianceFolder)
            ->getQuery()
            ->getResult();
    }
}
