<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Screening\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Screening\Entity\ScreeningAudit;
use App\Domain\Screening\Event\ScreeningCompletedEvent;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Infrastructure\Screening\Listener\ScreeningAuditLogListener;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ScreeningAuditLogListenerTest extends TestCase
{
    use ReflectionHelperTrait;

    private AuditLogRepositoryInterface&MockObject $auditLogRepository;
    private ScreeningAuditLogListener $listener;

    protected function setUp(): void
    {
        $this->auditLogRepository = $this->createMock(AuditLogRepositoryInterface::class);
        $this->listener = new ScreeningAuditLogListener($this->auditLogRepository);
    }

    private function event(): ScreeningCompletedEvent
    {
        $workspace = $this->createEntityState(Workspace::class, ['name' => 'Cabinet Durand', 'slugId' => 'wrk_1']);
        $user = $this->createEntityState(User::class, [
            'id' => Uuid::v7(),
            'firstName' => 'Marie',
            'lastName' => 'Curie',
            'email' => 'marie@cabinet.test',
        ]);
        $audit = $this->createEntityState(ScreeningAudit::class, [
            'slugId' => 'scr_aud_1',
            'query' => 'Jean Dupont',
            'totalMatches' => 3,
        ]);

        return new ScreeningCompletedEvent($workspace, $user, $audit, 5);
    }

    public function testWritesAScreeningPerformedAuditLogWithTheMatchCount(): void
    {
        $this->auditLogRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (AuditLog $log): bool => AuditEventType::SCREENING_PERFORMED === $log->eventName
                && 'Cabinet Durand' === $log->workspace?->name
                && 'Marie CURIE' === $log->payload['actor_name']
                && 'marie@cabinet.test' === $log->payload['actor_email']
                && 'Jean Dupont' === $log->payload['query_searched']
                && 'scr_aud_1' === $log->payload['audit_slug_id']
                && 3 === $log->payload['total_matches']
                && 5 === $log->payload['credits_cost']));

        ($this->listener)($this->event());
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenTheUserHasNoId(): void
    {
        $workspace = $this->createEntityState(Workspace::class, ['name' => 'Cabinet Durand', 'slugId' => 'wrk_1']);
        $user = $this->createEntityState(User::class, [
            'firstName' => 'Marie',
            'lastName' => 'Curie',
            'email' => 'marie@cabinet.test',
        ]);
        $audit = $this->createEntityState(ScreeningAudit::class, [
            'slugId' => 'scr_aud_1',
            'query' => 'Jean Dupont',
            'totalMatches' => 0,
        ]);

        // Le getter d'ID lève tant que l'entité n'a pas été persistée.
        $this->expectException(\LogicException::class);
        ($this->listener)(new ScreeningCompletedEvent($workspace, $user, $audit, 0));
    }
}
