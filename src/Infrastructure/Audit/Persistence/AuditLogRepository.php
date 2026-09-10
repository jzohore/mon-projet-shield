<?php

declare(strict_types=1);

namespace App\Infrastructure\Audit\Persistence;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;

/**
 * Adaptateur : Implémentation Doctrine du contrat d'Audit Log.
 *
 * @method AuditLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method AuditLog|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method AuditLog[]    findAll()
 * @method AuditLog[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
final readonly class AuditLogRepository implements AuditLogRepositoryInterface
{
    /** @var EntityRepository<AuditLog> */
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(AuditLog::class);
    }

    public function save(AuditLog $auditLog): void
    {
        $this->entityManager->persist($auditLog);
        $this->entityManager->flush();
    }

    /**
     * @return AuditLog[]
     */
    public function findByResourceId(string $resourceId): array
    {
        // On retourne toujours les événements du plus récent au plus ancien
        return $this->repository->findBy(
            ['resourceId' => $resourceId],
            ['occurredAt' => 'DESC']
        );
    }

    /**
     * @return AuditLog[]
     */
    public function findByEventName(string $eventName): array
    {
        return $this->repository->findBy(
            ['eventName' => $eventName],
            ['occurredAt' => 'DESC']
        );
    }

    public function findBySlugId(string $slugId): ?AuditLog
    {
        return $this->repository->findOneBy(['slugId' => $slugId]);
    }

    /**
     * @return Pagerfanta<AuditLog>
     */
    public function getAuditLogsList(Workspace $workspace, ?AuditEventType $eventType = null, ?string $searchQuery = null): Pagerfanta
    {
        $qb = $this->repository->createQueryBuilder('a')
            ->where('a.workspace = :workspace')
            ->setParameter('workspace', $workspace)
            ->orderBy('a.occurredAt', 'DESC')
        ;

        $visibleTypes = array_filter(
            AuditEventType::cases(),
            static fn (AuditEventType $type): bool => $type->isVisibleToWorkspace()
        );

        $qb->andWhere('a.eventName IN (:visibleTypes)')
            ->setParameter('visibleTypes', $visibleTypes);

        if ($eventType instanceof AuditEventType) {
            if ($eventType->isVisibleToWorkspace()) {
                $qb->andWhere('a.eventName = :eventType')
                    ->setParameter('eventType', $eventType);
            } else {
                $qb->andWhere('1 = 0');
            }
        }

        // 🔍 3. LA RECHERCHE TEXTUELLE (Magie PostgreSQL)
        if (!in_array($searchQuery, [null, '', '0'], true)) {
            $qb->andWhere("LOWER(JSON_GET_TEXT(a.payload, 'actor_name')) LIKE LOWER(:search)")
                ->setParameter('search', '%' . $searchQuery . '%');
        }

        return new Pagerfanta(new QueryAdapter($qb));
    }

    public function getGlobalAuditLogsList(
        int $page,
        int $perPage,
        ?string $workspaceQuery = null,
        ?AuditEventType $eventType = null,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null,
        ?string $actorQuery = null,
    ): Pagerfanta {
        $qb = $this->repository->createQueryBuilder('a')
            ->leftJoin('a.workspace', 'w')
            ->addSelect('w')
            ->orderBy('a.occurredAt', 'DESC');

        if ($eventType instanceof AuditEventType) {
            $qb->andWhere('a.eventName = :eventType')->setParameter('eventType', $eventType);
        }

        if ($from instanceof \DateTimeImmutable) {
            $qb->andWhere('a.occurredAt >= :from')->setParameter('from', $from);
        }

        if ($to instanceof \DateTimeImmutable) {
            $qb->andWhere('a.occurredAt <= :to')->setParameter('to', $to);
        }

        if (!in_array($workspaceQuery, [null, '', '0'], true)) {
            $qb->andWhere('LOWER(w.name) LIKE LOWER(:ws)')
                ->setParameter('ws', '%' . $workspaceQuery . '%');
        }

        if (!in_array($actorQuery, [null, '', '0'], true)) {
            $qb->andWhere(
                "LOWER(JSON_GET_TEXT(a.payload, 'actor_name')) LIKE LOWER(:actor)"
                . " OR LOWER(JSON_GET_TEXT(a.payload, 'actor_email')) LIKE LOWER(:actor)"
                . " OR LOWER(JSON_GET_TEXT(a.payload, 'revoked_by_email')) LIKE LOWER(:actor)"
            )->setParameter('actor', '%' . $actorQuery . '%');
        }

        $pager = new Pagerfanta(new QueryAdapter($qb));
        $pager->setMaxPerPage(max(1, $perPage));

        try {
            $pager->setCurrentPage(max(1, $page));
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        return $pager;
    }

    /**
     * @return AuditLog[]
     */
    public function findLatestLogs(int $limit = 5): array
    {
        return $this->repository->createQueryBuilder('a')
            ->orderBy('a.occurredAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AuditLog[]
     */
    public function findRecentByWorkspace(Workspace $workspace, int $limit = 6): array
    {
        $visibleTypes = array_filter(
            AuditEventType::cases(),
            static fn (AuditEventType $type): bool => $type->isVisibleToWorkspace(),
        );

        return $this->repository->createQueryBuilder('a')
            ->where('a.workspace = :workspace')
            ->andWhere('a.eventName IN (:visibleTypes)')
            ->setParameter('workspace', $workspace)
            ->setParameter('visibleTypes', $visibleTypes)
            ->orderBy('a.occurredAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
