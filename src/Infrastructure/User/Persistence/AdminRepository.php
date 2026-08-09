<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Persistence;

use App\Domain\User\Entity\Admin;
use App\Domain\User\Repository\AdminRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

/**
 * @method Admin|null find($id, $lockMode = null, $lockVersion = null)
 * @method Admin|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method Admin[]    findAll()
 * @method Admin[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
readonly class AdminRepository implements AdminRepositoryInterface
{
    /** @var EntityRepository<Admin> */
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Admin::class);
    }

    public function findByEmail(string $email): ?Admin
    {
        return $this->repository->findOneBy(['email' => $email]);
    }

    public function findById(Uuid|string $id): ?Admin
    {
        return $this->repository->find($id);
    }

    public function save(Admin $client, bool $flush = true): void
    {
        $this->entityManager->persist($client);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function findByMagicLink(string $magicLink): ?Admin
    {
        return $this->repository->findOneBy(['magicLinkToken' => $magicLink]);
    }
}
