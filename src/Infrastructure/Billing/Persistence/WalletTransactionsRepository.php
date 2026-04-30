<?php

namespace App\Infrastructure\Billing\Persistence;

use App\Domain\Wallet\Entity\WalletTransaction;
use App\Domain\Wallet\Repository\WalletTransactionsRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @method WalletTransaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method WalletTransaction|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method WalletTransaction[]    findAll()
 * @method WalletTransaction[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
class WalletTransactionsRepository implements WalletTransactionsRepositoryInterface
{
    /** @var EntityRepository<WalletTransaction> */
    private readonly EntityRepository $repository;
    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(WalletTransaction::class);
    }

    public function save(WalletTransaction $walletTransaction): void
    {
        $this->entityManager->persist($walletTransaction);
        $this->entityManager->flush();
    }

    public function getTransactionsList(string $workspaceSlugId): Pagerfanta
    {
        $qb = $this->repository->createQueryBuilder('walletTransaction')
            ->innerJoin('walletTransaction.workspace', 'workspace')
            ->andWhere('workspace.slugId = :workspaceSlugId')
            ->setParameter('workspaceSlugId', $workspaceSlugId)
            ->orderBy('walletTransaction.createdAt', 'DESC');

        return new Pagerfanta(new QueryAdapter($qb));
    }
}
