<?php

declare(strict_types=1);

namespace App\Tests\Application\ComplianceFolder;

use App\Application\Compliance\UseCase\ComplianceFolder\RevokeMeetingReportUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Entity\ValidatedMeetingReport;
use App\Domain\Compliance\Event\MeetingReportRevokedEvent;
use App\Domain\Compliance\Exception\MeetingReportNotFoundException;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Compliance\Repository\ValidatedMeetingReportRepositoryInterface;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

final class RevokeMeetingReportUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private ValidatedMeetingReportRepositoryInterface&MockObject $reportRepository;
    private ComplianceFolderRepositoryInterface&MockObject $folderRepository;
    private TransactionManagerInterface&Stub $transactionManager;
    private CurrentUserProvider&Stub $userProvider;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private ComplianceFolder $folder;
    private RevokeMeetingReportUseCase $useCase;

    protected function setUp(): void
    {
        $this->reportRepository = $this->createMock(ValidatedMeetingReportRepositoryInterface::class);
        $this->folderRepository = $this->createMock(ComplianceFolderRepositoryInterface::class);
        $this->transactionManager = $this->createStub(TransactionManagerInterface::class);
        $this->userProvider = $this->createStub(CurrentUserProvider::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $user = $this->createEntityState(User::class, [
            'firstName' => 'marie',
            'lastName' => 'curie',
            'email' => 'marie@kysure.test',
        ]);
        $this->userProvider->method('getUser')->willReturn($user);

        $this->transactionManager->method('transactional')
            ->willReturnCallback(static fn (callable $operation): mixed => $operation());

        $this->folder = $this->createEntityState(BusinessFolder::class, [
            'slugId' => 'comp_fol_1',
            'history' => [],
        ]);

        $this->useCase = new RevokeMeetingReportUseCase(
            $this->reportRepository,
            $this->folderRepository,
            $this->transactionManager,
            $this->userProvider,
            $this->eventDispatcher,
        );
    }

    public function testRevokesReportPersistsBothAndDispatchesEvent(): void
    {
        $report = $this->report(revokedAt: null);

        $this->reportRepository->method('findBySlugId')->willReturn($report);
        $this->reportRepository->expects($this->once())->method('save')->with($this->identicalTo($report));
        $this->folderRepository->expects($this->once())->method('save')->with($this->identicalTo($this->folder));

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (MeetingReportRevokedEvent $e): bool => 'meeting_report_1' === $e->reportSlugId
                && 'comp_fol_1' === $e->folderSlugId
                && 4 === $e->version
                && 'profil de risque erroné' === $e->reason
                && 'marie@kysure.test' === $e->revokedByEmail
                && 'Marie CURIE' === $e->revokedByName
                && '' !== $e->reportId));

        ($this->useCase)('meeting_report_1', '  profil de risque erroné  ');

        self::assertTrue($report->isRevoked());
        self::assertSame('profil de risque erroné', $report->revokeReason);
        self::assertSame("Rapport d'entretien révoqué", $this->folder->history[0]['title']);
        self::assertStringContainsString('Version 4 révoquée par Marie CURIE', $this->folder->history[0]['description']);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenReportNotFound(): void
    {
        $this->reportRepository->method('findBySlugId')->willReturn(null);
        $this->reportRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(MeetingReportNotFoundException::class);

        ($this->useCase)('meeting_report_missing', 'un motif');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenReasonIsBlank(): void
    {
        $this->reportRepository->method('findBySlugId')->willReturn($this->report(revokedAt: null));
        $this->reportRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);

        ($this->useCase)('meeting_report_1', '   ');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenReportIsAlreadyRevoked(): void
    {
        $this->reportRepository->method('findBySlugId')->willReturn(
            $this->report(revokedAt: new \DateTimeImmutable('2026-08-01 12:00:00')),
        );
        $this->reportRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);

        ($this->useCase)('meeting_report_1', 'nouvelle tentative');
    }

    private function report(?\DateTimeImmutable $revokedAt): ValidatedMeetingReport
    {
        return $this->createEntityState(ValidatedMeetingReport::class, [
            'id' => Uuid::v7(),
            'slugId' => 'meeting_report_1',
            'version' => 4,
            'complianceFolder' => $this->folder,
            'revokedAt' => $revokedAt,
        ]);
    }
}
