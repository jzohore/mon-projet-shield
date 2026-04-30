<?php

namespace App\Infrastructure\Billing\Persistence;

use App\Domain\Billing\Entity\Subscription;
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
    public function __construct(private EntityManagerInterface $entityManager)
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
}
