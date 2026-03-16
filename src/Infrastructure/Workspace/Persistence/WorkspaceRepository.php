<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Persistence;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/**
 * @method Workspace|null find($id, $lockMode = null, $lockVersion = null)
 * @method Workspace|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method Workspace[]    findAll()
 * @method Workspace[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
final readonly class WorkspaceRepository implements WorkspaceRepositoryInterface
{
    /** @var EntityRepository<Workspace> */
    private EntityRepository $repository;
    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Workspace::class);
    }

    public function save(Workspace $workspace): void
    {
        $this->entityManager->persist($workspace);
        $this->entityManager->flush();
    }

    public function findOneBySlug(string $slug): ?Workspace
    {
        return $this->repository->findOneBy(['slugId' => $slug]);
    }

    /**
     * @return Workspace[]|User[]
     */
    public function findMembersByWorkspaceId(string $workspaceId): array
    {
        return $this->repository->findBy(['slugId' => $workspaceId]);
    }

    public function findOneByName(?string $name): ?Workspace
    {
        return $this->repository->findOneBy(['name' => $name]);
    }
}
