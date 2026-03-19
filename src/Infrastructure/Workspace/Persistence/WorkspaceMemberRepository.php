<?php

namespace App\Infrastructure\Workspace\Persistence;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/**
 * @method WorkspaceMember|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkspaceMember|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method WorkspaceMember[]    findAll()
 * @method WorkspaceMember[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
final readonly class WorkspaceMemberRepository implements WorkspaceMemberRepositoryInterface
{
    /** @var EntityRepository<WorkspaceMember> */
    private EntityRepository $repository;
    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(WorkspaceMember::class);
    }

    public function save(WorkspaceMember $workspaceMember): void
    {
        $this->entityManager->persist($workspaceMember);
        $this->entityManager->flush();
    }

    public function findByWorkspaceAndUser(Workspace $workspace, User $user): ?WorkspaceMember
    {
        return $this->repository->findOneBy(['workspace' => $workspace, 'user' => $user]);
    }

    /**
     * @param string $workspaceId
     * @return array<int, WorkspaceMember>
     */
    public function findByWorkspace(string $workspaceId): array
    {
        return $this->repository->findBy(['workspace' => $workspaceId]);
    }

    public function delete(WorkspaceMember $workspaceMember): void
    {
        $this->entityManager->remove($workspaceMember);
        $this->entityManager->flush();
    }

    /**
     * @param User $user
     * @return array<int, WorkspaceMember>
     */
    public function findByUser(User $user): array
    {
        return $this->repository->findBy(['user' => $user]);
    }

    public function findOneByUser(User $user): ?WorkspaceMember
    {
        return $this->repository->findOneBy(['user' => $user]);
    }

    public function isUserAdminOfWorkspace(User $user, Workspace $workspace): bool
    {
        $member = $this->repository->findOneBy([
            'user' => $user,
            'workspace' => $workspace,
        ]);

        // Si le membre n'existe pas, il n'est pas admin
        if (null === $member) {
            return false;
        }

        return $member->isAdmin();
    }
}
