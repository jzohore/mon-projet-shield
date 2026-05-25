<?php

namespace App\Infrastructure\Compliance\Persistence;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Exception\ComplianceFolderNotFoundException;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

/**
 * @method ComplianceFolder|null find($id, $lockMode = null, $lockVersion = null)
 * @method ComplianceFolder|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method ComplianceFolder[]    findAll()
 * @method ComplianceFolder[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
class ComplianceFolderRepository implements ComplianceFolderRepositoryInterface
{
    /** @var EntityRepository<ComplianceFolder> */
    private readonly EntityRepository $repository;
    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(ComplianceFolder::class);
    }

    public function save(ComplianceFolder $folder, bool $flush = true): void
    {
        $this->entityManager->persist($folder);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function remove(ComplianceFolder $folder, bool $flush = true): void
    {
        $this->entityManager->remove($folder);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function findById(Uuid|string $id): ComplianceFolder
    {
        $folder = $this->repository->find($id);

        if (!$folder) {
            throw ComplianceFolderNotFoundException::withId((string) $id);
        }

        return $folder;
    }

    public function findByReference(string $reference): ComplianceFolder
    {
        $folder = $this->repository->findOneBy(['reference' => $reference]);

        if (!$folder) {
            throw ComplianceFolderNotFoundException::withReference($reference);
        }

        return $folder;
    }

    /**
     * @return array<ComplianceFolder>
     */
    public function findAllActiveByWorkspace(Workspace $workspace): array
    {
        return $this->repository->createQueryBuilder('cf')
            ->andWhere('cf.workspace = :workspace')
            ->andWhere('cf.status = :status')->setParameter('status', ComplianceFolderStatus::APPROVED)
            ->setParameter('workspace', $workspace)
            ->getQuery()
            ->getResult();
    }

    public function countDraftsForWorkspace(Workspace $workspace): int
    {
        return (int) $this->repository->createQueryBuilder('cf')
            ->select('COUNT(cf.id)')
            ->leftJoin('cf.workspace', 'workspace')
            ->where('workspace = :workspace')
            ->andWhere('cf.status = :state')
            ->setParameter('workspace', $workspace)
            ->setParameter('state', ComplianceFolderStatus::DRAFT)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
