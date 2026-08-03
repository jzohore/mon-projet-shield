<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Persistence;

use App\Domain\User\Entity\Client;
use App\Domain\User\Repository\ClientRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

/**
 * @method Client|null find($id, $lockMode = null, $lockVersion = null)
 * @method Client|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method Client[]    findAll()
 * @method Client[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
readonly class ClientRepository implements ClientRepositoryInterface
{
    /** @var EntityRepository<Client> */
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Client::class);
    }

    public function findByEmail(string $email): ?Client
    {
        return $this->repository->findOneBy(['email' => $email]);
    }

    public function findById(Uuid|string $id): ?Client
    {
        return $this->repository->find($id);
    }

    public function save(Client $client, bool $flush = true): void
    {
        $this->entityManager->persist($client);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function findByMagicLink(string $magicLink): ?Client
    {
        return $this->repository->findOneBy(['magicLinkToken' => $magicLink]);
    }
}
