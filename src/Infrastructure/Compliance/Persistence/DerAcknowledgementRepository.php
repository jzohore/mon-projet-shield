<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Persistence;

use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\DerAcknowledgement;
use App\Domain\Compliance\Repository\DerAcknowledgementRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

/**
 * @method DerAcknowledgement|null find($id, $lockMode = null, $lockVersion = null)
 * @method DerAcknowledgement|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method DerAcknowledgement[]    findAll()
 * @method DerAcknowledgement[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
readonly class DerAcknowledgementRepository implements DerAcknowledgementRepositoryInterface
{
    /** @var EntityRepository<DerAcknowledgement> */
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(DerAcknowledgement::class);
    }

    public function save(DerAcknowledgement $acknowledgement, bool $flush = true): void
    {
        $this->entityManager->persist($acknowledgement);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function findById(Uuid|string $id): ?DerAcknowledgement
    {
        return $this->repository->find($id);
    }

    public function findBySlugId(string $slugId): ?DerAcknowledgement
    {
        return $this->repository->findOneBy(['slugId' => $slugId]);
    }

    public function findInForceByDocument(ComplianceDocument $document): ?DerAcknowledgement
    {
        return $this->repository->createQueryBuilder('a')
            ->andWhere('a.document = :document')
            ->andWhere('a.revokedAt IS NULL')
            ->orderBy('a.acknowledgedAt', 'DESC')
            ->setParameter('document', $document)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
