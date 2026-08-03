<?php

declare(strict_types=1);

namespace App\Infrastructure\Firm\Persistence;

use App\Domain\Firm\Entity\RegulatoryProfile;
use App\Domain\Firm\Repository\RegulatoryProfileRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

/**
 * @method RegulatoryProfile|null find($id, $lockMode = null, $lockVersion = null)
 * @method RegulatoryProfile|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method RegulatoryProfile[]    findAll()
 * @method RegulatoryProfile[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
class RegulatoryProfileRepository implements RegulatoryProfileRepositoryInterface
{
    /** @var EntityRepository<RegulatoryProfile> */
    private readonly EntityRepository $repository;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(RegulatoryProfile::class);
    }

    public function save(RegulatoryProfile $regulatoryProfile, bool $isFlush = true): void
    {
        $this->entityManager->persist($regulatoryProfile);
        if ($isFlush) {
            $this->entityManager->flush();
        }
    }

    public function findById(Uuid|string $id): ?RegulatoryProfile
    {
        return $this->repository->find($id);
    }

    public function findOneByWorkspace(Workspace $workspace): ?RegulatoryProfile
    {
        return $this->repository->findOneBy(['workspace' => $workspace]);
    }
}
