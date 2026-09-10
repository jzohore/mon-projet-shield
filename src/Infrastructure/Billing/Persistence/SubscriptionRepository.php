<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Persistence;

use App\Domain\Billing\Entity\Subscription;
use App\Domain\Billing\Enum\SubscriptionStatus;
use App\Domain\Billing\Exception\SubscriptionNotFoundException;
use App\Domain\Billing\Repository\SubscriptionRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;

/**
 * @method Subscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method Subscription|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method Subscription[]    findAll()
 * @method Subscription[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    /** @var EntityRepository<Subscription> */
    private readonly EntityRepository $repository;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Subscription::class);
    }

    public function getByStripeId(string $stripeSubscriptionId): Subscription
    {
        $sub = $this->repository->findOneBy(['stripeSubscriptionId' => $stripeSubscriptionId]);

        if (null === $sub) {
            throw SubscriptionNotFoundException::withSubscriptionId($stripeSubscriptionId);
        }

        return $sub;
    }

    public function findByStripeId(string $stripeSubscriptionId): ?Subscription
    {
        return $this->repository->findOneBy(['stripeSubscriptionId' => $stripeSubscriptionId]);
    }

    public function save(Subscription $subscription): void
    {
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();
    }

    /**
     * @param SubscriptionStatus[] $statuses
     */
    public function countByStatuses(array $statuses): int
    {
        if ([] === $statuses) {
            return 0;
        }

        return (int) $this->repository->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            // 🪄 MAGIE DOCTRINE : Le IN() supporte nativement un tableau d'Enums PHP 8.1
            ->where('s.status IN (:statuses)')
            ->setParameter('statuses', $statuses)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getPaginatedSubscriptions(
        int $page,
        int $perPage,
        ?string $search = null,
        ?SubscriptionStatus $status = null,
    ): Pagerfanta {
        $qb = $this->repository->createQueryBuilder('s')
            ->leftJoin('s.workspace', 'w')
            ->addSelect('w')
            ->orderBy('s.createdAt', 'DESC');

        if ($status instanceof SubscriptionStatus) {
            $qb->andWhere('s.status = :status')->setParameter('status', $status);
        }

        if (!in_array($search, [null, '', '0'], true)) {
            $qb->andWhere('LOWER(w.name) LIKE LOWER(:q) OR LOWER(s.stripeSubscriptionId) LIKE LOWER(:q) OR LOWER(s.planReference) LIKE LOWER(:q)')
                ->setParameter('q', '%' . $search . '%');
        }

        $pager = new Pagerfanta(new QueryAdapter($qb));
        $pager->setMaxPerPage(max(1, $perPage));

        try {
            $pager->setCurrentPage(max(1, $page));
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        return $pager;
    }
}
