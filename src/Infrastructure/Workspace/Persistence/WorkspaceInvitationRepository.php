<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Persistence;

use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Enum\InvitationStatus;
use App\Domain\Workspace\Exception\WorkspaceInvitationNotFoundException;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

/**
 * @method WorkspaceInvitation|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkspaceInvitation|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method WorkspaceInvitation[]    findAll()
 * @method WorkspaceInvitation[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
final readonly class WorkspaceInvitationRepository implements WorkspaceInvitationRepositoryInterface
{
    /** @var EntityRepository<WorkspaceInvitation> */
    private EntityRepository $repository;
    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(WorkspaceInvitation::class);
    }
    public function save(WorkspaceInvitation $workspaceInvitation, bool $flush = true): void
    {
        $this->entityManager->persist($workspaceInvitation);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * @return array<int, WorkspaceInvitation>
     */
    public function findByWorkspace(Workspace $workspace): array
    {
        return $this->repository->findBy(['workspace' => $workspace], ['createdAt' => 'DESC']);
    }

    public function findByEmail(string $email): ?WorkspaceInvitation
    {
        return $this->repository->findOneBy(['email' => $email]);
    }

    public function findBySlugId(string $slugId): ?WorkspaceInvitation
    {
        return $this->repository->findOneBy(['slugId' => $slugId]);
    }

    public function delete(WorkspaceInvitation $workspaceInvitation): void
    {
        $em = $this->entityManager;
        $em->remove($workspaceInvitation);
        $em->flush();
    }

    public function countMemberInvitation(?string $workspaceId = null): bool|float|int|string|null
    {
        return $this->repository->createQueryBuilder('wi')
            ->select('COUNT(wi.id)')
            ->leftJoin('wi.workspace', 'w')
            ->where('w.slugId = :workspaceId')
            ->setParameter('workspaceId', $workspaceId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByToken(string $token): ?WorkspaceInvitation
    {
        return $this->repository->findOneBy(['magicLinkToken' => $token]);
    }

    public function getById(Uuid $id): WorkspaceInvitation
    {
        $invitation = $this->repository->find($id);

        if (null === $invitation) {
            throw WorkspaceInvitationNotFoundException::withId($id);
        }

        return $invitation;
    }

    public function hasPendingInvitation(Workspace $workspace, string $email): bool
    {
        $pendingCount = $this->repository->count([
            'workspace' => $workspace,
            'email' => $email,
            'invitationStatus' => InvitationStatus::PENDING,
        ]);

        return $pendingCount > 0;
    }
}
