<?php

declare(strict_types=1);

namespace App\Infrastructure\KYC\Persistence;

use App\Domain\Compliance\Enum\DocumentType;
use App\Domain\Kyc\Entity\KycDocument;
use App\Domain\Kyc\Entity\KycFolder;
use App\Domain\Kyc\Entity\Stakeholder;
use App\Domain\Kyc\Repository\KycDocumentRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/**
 * @method KycDocument|null find($id, $lockMode = null, $lockVersion = null)
 * @method KycDocument|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method KycDocument[]    findAll()
 * @method KycDocument[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
class KycDocumentRepository implements KycDocumentRepositoryInterface
{
    /** @var EntityRepository<KycDocument> */
    private readonly EntityRepository $repository;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(KycDocument::class);
    }

    public function save(KycDocument $kycDocument): void
    {
        $this->entityManager->persist($kycDocument);
        $this->entityManager->flush();
    }

    public function findBySlugId(string $slugId): ?KycDocument
    {
        return $this->repository->findOneBy(['slugId' => $slugId]);
    }

    /**
     * @return array<int, KycDocument>
     */
    public function findPendingDocuments(): array
    {
        return $this->repository->findBy(['status' => 'uploaded']);
    }

    public function findOneByContext(KycFolder $folder, DocumentType $type, ?Stakeholder $stakeholder = null): ?KycDocument
    {
        $qb = $this->repository->createQueryBuilder('kycDocument')
            ->andWhere('kycDocument.folder = :folder')
            ->andWhere('kycDocument.type = :type')
            ->setParameter('folder', $folder)
            ->setParameter('type', $type);
        if ($stakeholder instanceof Stakeholder) {
            $qb->andWhere('kycDocument.stakeholder = :stakeholder')
                ->setParameter('stakeholder', $stakeholder);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
