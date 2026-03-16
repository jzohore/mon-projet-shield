<?php

declare(strict_types=1);

namespace App\Infrastructure\Audit\Persistence;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
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

    /**
     * @param string $slugId
     * @return AuditLog|null
     */
    public function findBySlugId(string $slugId): ?AuditLog
    {
        return $this->repository->findOneBy(['slugId' => $slugId]);
    }

    /**
     * @return Pagerfanta<AuditLog>
     */
    public function getAuditLogsList(?AuditEventType $type = null): Pagerfanta
    {
        $qb = $this->repository->createQueryBuilder('auditLog')
        ->orderBy('auditLog.occurredAt', 'DESC')
        ;

        if ($type) {
            $qb->andWhere('a.type = :type')
                ->setParameter('type', $type);
        }

        return new Pagerfanta(new QueryAdapter($qb));
    }
}
