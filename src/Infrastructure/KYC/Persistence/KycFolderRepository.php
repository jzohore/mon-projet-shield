<?php

namespace App\Infrastructure\KYC\Persistence;

use App\Domain\Kyc\Entity\KycFolder;
use App\Domain\Kyc\Enum\KycFolderStatus;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @method KycFolder|null find($id, $lockMode = null, $lockVersion = null)
 * @method KycFolder|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method KycFolder[]    findAll()
 * @method KycFolder[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
class KycFolderRepository implements KycFolderRepositoryInterface
{
    /** @var EntityRepository<KycFolder> */
    private EntityRepository $repository;
    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(KycFolder::class);
    }

    public function save(KycFolder $kycFolder): void
    {
        $this->entityManager->persist($kycFolder);
        $this->entityManager->flush();
    }

    public function findBySlugId(string $slugId): ?KycFolder
    {
        return $this->repository->findOneBy(['slugId' => $slugId]);
    }

    public function getKycFolderList(string $workspaceSlugId, ?string $search = null): Pagerfanta
    {
        $qb = $this->repository->createQueryBuilder('kycFolder')
            ->leftJoin('kycFolder.workspace', 'workspace')
            ->andWhere('workspace.slugId = :workspaceSlugid')
            ->addSelect('workspace')
            ->setParameter('workspaceSlugid', $workspaceSlugId)
        ->orderBy('kycFolder.createdAt', 'DESC');

        if ($search) {
            $qb->andWhere('kycFolder.contactEmail LIKE :search 
            OR kycFolder.reference LIKE :search
            ')
                ->setParameter('search', '%' . $search . '%');
        }

        return new Pagerfanta(new QueryAdapter($qb));
    }

    public function findOneByEmailAndStatus(string $email, KycFolderStatus $status, string $workspaceId): ?KycFolder
    {
        return $qb = $this->repository->createQueryBuilder('kycFolder')
            ->leftJoin('kycFolder.workspace', 'workspace')
            ->andWhere('workspace.slugId = :workspaceSlugid')
            ->andWhere('kycFolder.contactEmail = :email')
            ->andWhere('kycFolder.status = :status')
            ->setParameter('email', $email)
            ->setParameter('status', $status)
            ->setParameter('workspaceSlugid', $workspaceId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByToken(string $shareToken): ?KycFolder
    {
        return $this->repository->findOneBy(['shareToken' => $shareToken]);
    }

    public function remove(KycFolder $kycFolder): void
    {
        $this->entityManager->remove($kycFolder);
        $this->entityManager->flush();
    }
}
