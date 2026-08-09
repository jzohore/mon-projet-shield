<?php

declare(strict_types=1);

namespace App\Infrastructure\KYC\Persistence;

use App\Domain\Kyc\Entity\Stakeholder;
use App\Domain\Kyc\Repository\StakeholderRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/**
 * @method Stakeholder|null find($id, $lockMode = null, $lockVersion = null)
 * @method Stakeholder|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method Stakeholder[]    findAll()
 * @method Stakeholder[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
class StakeholderRepository implements StakeholderRepositoryInterface
{
    /** @var EntityRepository<Stakeholder> */
    private readonly EntityRepository $repository;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Stakeholder::class);
    }

    public function save(Stakeholder $stakeholder): void
    {
        $this->entityManager->persist($stakeholder);
        $this->entityManager->flush();
    }

    public function findBySlugId(string $slugId): ?Stakeholder
    {
        return $this->repository->findOneBy(['slugId' => $slugId]);
    }

    public function remove(Stakeholder $stakeholder): void
    {
        $this->entityManager->remove($stakeholder);
        $this->entityManager->flush();
    }
}
