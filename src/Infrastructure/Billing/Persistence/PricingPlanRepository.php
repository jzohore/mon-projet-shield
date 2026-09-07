<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Persistence;

use App\Domain\Billing\Entity\PricingPlan;
use App\Domain\Billing\Enum\PricingPlanKind;
use App\Domain\Billing\Repository\PricingPlanRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/**
 * @method PricingPlan|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 */
class PricingPlanRepository implements PricingPlanRepositoryInterface
{
    /** @var EntityRepository<PricingPlan> */
    private readonly EntityRepository $repository;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(PricingPlan::class);
    }

    public function save(PricingPlan $plan): void
    {
        $this->entityManager->persist($plan);
        $this->entityManager->flush();
    }

    public function findByKey(string $planKey): ?PricingPlan
    {
        return $this->repository->findOneBy(['planKey' => $planKey]);
    }

    public function getByKey(string $planKey): PricingPlan
    {
        $plan = $this->findByKey($planKey);

        if (!$plan instanceof PricingPlan) {
            throw new \DomainException(sprintf('Offre "%s" introuvable. Lancez la commande app:billing:sync-pricing.', $planKey));
        }

        if (!$plan->isProvisioned()) {
            throw new \DomainException(sprintf('Offre "%s" non provisionnée sur Stripe. Lancez app:billing:sync-pricing.', $planKey));
        }

        return $plan;
    }

    /**
     * @return PricingPlan[]
     */
    public function findByKind(PricingPlanKind $kind): array
    {
        return $this->repository->findBy(
            ['kind' => $kind, 'isActive' => true],
            ['unitAmountCents' => 'ASC'],
        );
    }

    /**
     * @return PricingPlan[]
     */
    public function findAllActive(): array
    {
        return $this->repository->findBy(['isActive' => true], ['kind' => 'ASC', 'unitAmountCents' => 'ASC']);
    }
}
