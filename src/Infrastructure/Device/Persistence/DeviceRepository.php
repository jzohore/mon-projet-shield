<?php

declare(strict_types=1);

namespace App\Infrastructure\Device\Persistence;

use App\Domain\Device\Device;
use App\Domain\Device\DeviceRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/**
 *
 * @method Device|null find($id, $lockMode = null, $lockVersion = null)
 * @method Device|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method Device[]    findAll()
 * @method Device[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
final readonly class DeviceRepository implements DeviceRepositoryInterface
{
    /** @var EntityRepository<Device> */
    private EntityRepository $repository;
    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Device::class);
    }

    public function save(Device $device): void
    {
        $this->entityManager->persist($device);
        $this->entityManager->flush();
    }

    public function findBySlugId(string $slugId): ?Device
    {
        return $this->repository->findOneBy(['slugId' => $slugId]);
    }
}
