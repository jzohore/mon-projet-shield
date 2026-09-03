<?php

declare(strict_types=1);

namespace App\Tests\Application\ComplianceFolder;

use App\Application\Compliance\UseCase\ComplianceFolder\BuildHolisticMeetingReportUseCase;
use App\Application\Compliance\UseCase\ComplianceFolder\ValidateMeetingReportUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Entity\MeetingRecording;
use App\Domain\Compliance\Entity\ValidatedMeetingReport;
use App\Domain\Compliance\Enum\AdvisoryRiskProfile;
use App\Domain\Compliance\Enum\MeetingProcessingStatus;
use App\Domain\Compliance\Event\MeetingReportValidatedEvent;
use App\Domain\Compliance\Exception\ComplianceFolderNotFoundException;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Compliance\Repository\MeetingRecordRepositoryInterface;
use App\Domain\Compliance\Repository\ValidatedMeetingReportRepositoryInterface;
use App\Domain\Compliance\ValueObject\MeetingReportAdjustments;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ValidateMeetingReportUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private ComplianceFolderRepositoryInterface&MockObject $folderRepository;
    private ValidatedMeetingReportRepositoryInterface&MockObject $reportRepository;
    private MeetingRecordRepositoryInterface&Stub $recordRepository;
    private CurrentUserProvider&Stub $userProvider;
    private TransactionManagerInterface&Stub $transactionManager;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private User $user;
    private ValidateMeetingReportUseCase $useCase;

    protected function setUp(): void
    {
        $this->folderRepository = $this->createMock(ComplianceFolderRepositoryInterface::class);
        $this->reportRepository = $this->createMock(ValidatedMeetingReportRepositoryInterface::class);
        $this->recordRepository = $this->createStub(MeetingRecordRepositoryInterface::class);
        $this->userProvider = $this->createStub(CurrentUserProvider::class);
        $this->transactionManager = $this->createStub(TransactionManagerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->user = $this->createEntityState(User::class, [
            'firstName' => 'marie',
            'lastName' => 'curie',
            'email' => 'marie@kysure.test',
        ]);
        $this->userProvider->method('getUser')->willReturn($this->user);

        // La transaction exécute simplement son opération.
        $this->transactionManager->method('transactional')
            ->willReturnCallback(static fn (callable $operation): mixed => $operation());

        $this->useCase = new ValidateMeetingReportUseCase(
            $this->folderRepository,
            $this->reportRepository,
            new BuildHolisticMeetingReportUseCase($this->recordRepository),
            $this->transactionManager,
            $this->userProvider,
            $this->eventDispatcher,
        );
    }

    public function testValidatesTheDraftFreezesItAndDispatchesEvent(): void
    {
        $folder = $this->folder(MeetingProcessingStatus::DONE);

        $this->folderRepository->method('findOneBySlugId')->willReturn($folder);
        $this->reportRepository->method('findInForceByFolder')->willReturn(null);
        $this->reportRepository->method('findLatestVersionNumber')->willReturn(0);
        $this->recordRepository->method('findActiveByFolder')->willReturn([$this->explorableRecording()]);

        $this->reportRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(ValidatedMeetingReport::class));

        $this->folderRepository->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($folder));

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (MeetingReportValidatedEvent $e): bool => 'comp_fol_1' === $e->folderSlugId
                && 1 === $e->version
                && 'marie@kysure.test' === $e->validatedByEmail
                && 'Marie CURIE' === $e->validatedByName
                && str_starts_with($e->reportSlugId, 'meeting_report_')
                && '' !== $e->reportId
                && false === $e->adjusted));

        $slugId = ($this->useCase)('comp_fol_1');

        self::assertStringStartsWith('meeting_report_', $slugId);
        self::assertNotEmpty($folder->history);
        self::assertSame("Rapport d'entretien validé", $folder->history[0]['title']);
        self::assertSame('Version 1 figée par Marie CURIE', $folder->history[0]['description']);
    }

    public function testAppliesCgpAdjustmentsToTheFrozenContent(): void
    {
        $folder = $this->folder(MeetingProcessingStatus::DONE);

        $this->folderRepository->method('findOneBySlugId')->willReturn($folder);
        $this->reportRepository->method('findInForceByFolder')->willReturn(null);
        $this->reportRepository->method('findLatestVersionNumber')->willReturn(0);
        $this->recordRepository->method('findActiveByFolder')->willReturn([$this->explorableRecording()]);
        $this->folderRepository->expects($this->once())->method('save');

        $this->reportRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static function (ValidatedMeetingReport $report): bool {
                self::assertSame('Synthèse revue par le CGP.', $report->content['summary']);
                self::assertSame('Prudent', $report->content['riskProfile']);
                self::assertTrue($report->content['isAdjusted']);

                return true;
            }));

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (MeetingReportValidatedEvent $e): bool => $e->adjusted));

        ($this->useCase)('comp_fol_1', MeetingReportAdjustments::fromInput('  Synthèse revue par le CGP.  ', AdvisoryRiskProfile::PRUDENT));

        self::assertStringContainsString('(texte ajusté par le CGP)', $folder->history[0]['description']);
    }

    public function testNextVersionIsLatestPlusOne(): void
    {
        $folder = $this->folder(MeetingProcessingStatus::DONE);

        $this->folderRepository->method('findOneBySlugId')->willReturn($folder);
        $this->reportRepository->method('findInForceByFolder')->willReturn(null);
        $this->reportRepository->method('findLatestVersionNumber')->willReturn(2);
        $this->recordRepository->method('findActiveByFolder')->willReturn([$this->explorableRecording()]);
        $this->reportRepository->expects($this->once())->method('save');
        $this->folderRepository->expects($this->once())->method('save');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (MeetingReportValidatedEvent $e): bool => 3 === $e->version));

        ($this->useCase)('comp_fol_1');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenFolderNotFound(): void
    {
        $this->folderRepository->method('findOneBySlugId')->willReturn(null);
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(ComplianceFolderNotFoundException::class);

        ($this->useCase)('comp_fol_missing');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenMeetingAnalysisStillRunning(): void
    {
        $this->folderRepository->method('findOneBySlugId')->willReturn($this->folder(MeetingProcessingStatus::ANALYZING));
        $this->reportRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);

        ($this->useCase)('comp_fol_1');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenAReportIsAlreadyInForce(): void
    {
        $folder = $this->folder(MeetingProcessingStatus::DONE);
        $this->folderRepository->method('findOneBySlugId')->willReturn($folder);
        $this->reportRepository->method('findInForceByFolder')->willReturn(
            $this->createEntityState(ValidatedMeetingReport::class, ['slugId' => 'meeting_report_x', 'version' => 1, 'revokedAt' => null]),
        );
        $this->reportRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);

        ($this->useCase)('comp_fol_1');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenDraftHasNoExploitableContent(): void
    {
        $folder = $this->folder(MeetingProcessingStatus::DONE);
        $this->folderRepository->method('findOneBySlugId')->willReturn($folder);
        $this->reportRepository->method('findInForceByFolder')->willReturn(null);
        $this->recordRepository->method('findActiveByFolder')->willReturn([
            $this->createEntityState(MeetingRecording::class, [
                'slugId' => 'meeting_rec_1',
                'recordedAt' => new \DateTimeImmutable('2026-08-01 10:00:00'),
                'geminiRawOutput' => ['executiveSummary' => 'Test ou hors sujet'],
            ]),
        ]);
        $this->reportRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);

        ($this->useCase)('comp_fol_1');
    }

    private function folder(MeetingProcessingStatus $status): ComplianceFolder
    {
        return $this->createEntityState(BusinessFolder::class, [
            'slugId' => 'comp_fol_1',
            'meetingProcessingStatus' => $status,
            'history' => [],
        ]);
    }

    private function explorableRecording(): MeetingRecording
    {
        return $this->createEntityState(MeetingRecording::class, [
            'slugId' => 'meeting_rec_1',
            'recordedAt' => new \DateTimeImmutable('2026-08-20 09:00:00', new \DateTimeZone('Europe/Paris')),
            'geminiRawOutput' => [
                'executiveSummary' => 'Entretien exploitable : objectifs et patrimoine.',
                'riskProfileDetected' => 'Prudent',
            ],
        ]);
    }
}
