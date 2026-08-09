<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Persistence;

use App\Domain\Billing\Entity\Subscription;
use App\Domain\Billing\Enum\SubscriptionStatus;
use App\Domain\Billing\Exception\SubscriptionNotFoundException;
use App\Domain\Billing\Repository\SubscriptionRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

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
}
