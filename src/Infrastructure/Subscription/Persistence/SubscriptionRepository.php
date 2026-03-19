<?php

namespace App\Infrastructure\Subscription\Persistence;

use App\Domain\Subscription\Entity\Subscription;
use App\Domain\Subscription\Repository\SubscriptionRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/**
 * @method Subscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method Subscription|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method Subscription[]    findAll()
 * @method Subscription[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
final readonly class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    /** @var EntityRepository<Subscription> */
    private EntityRepository $repository;
    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Subscription::class);
    }

    public function save(Subscription $subscription): void
    {
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();
    }

    public function findByStripeId(string $stripeId): ?Subscription
    {
        return $this->repository->findOneBy(['stripeSubscriptionId' => $stripeId]);
    }
}
