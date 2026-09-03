<?php

declare(strict_types=1);

namespace App\Tests\Application\ComplianceFolder;

use App\Application\Compliance\UseCase\ComplianceFolder\BuildHolisticMeetingReportUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\MeetingRecording;
use App\Domain\Compliance\Repository\MeetingRecordRepositoryInterface;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class BuildHolisticMeetingReportUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private MeetingRecordRepositoryInterface&Stub $recordRepository;
    private BuildHolisticMeetingReportUseCase $useCase;

    protected function setUp(): void
    {
        $this->recordRepository = $this->createStub(MeetingRecordRepositoryInterface::class);
        $this->useCase = new BuildHolisticMeetingReportUseCase($this->recordRepository);
    }

    public function testMergesSessionsChronologicallyEvenWhenRepositoryReturnsThemUnordered(): void
    {
        $older = $this->recording('meeting_rec_old', '2026-08-20 09:00:00', [
            'executiveSummary' => 'Premier entretien : prise de contact.',
        ]);
        $newer = $this->recording('meeting_rec_new', '2026-08-27 14:30:00', [
            'executiveSummary' => 'Second entretien : objectifs patrimoniaux.',
        ]);

        // Le repository les renvoie dans le désordre : le use case doit re-trier.
        $this->recordRepository->method('findActiveByFolder')->willReturn([$newer, $older]);

        $dto = ($this->useCase)($this->createEntityState(BusinessFolder::class));

        self::assertTrue($dto->isExplorable);
        self::assertSame(['meeting_rec_old', 'meeting_rec_new'], $dto->slugId);

        $posOld = strpos($dto->executiveSummary, 'prise de contact');
        $posNew = strpos($dto->executiveSummary, 'objectifs patrimoniaux');
        self::assertNotFalse($posOld);
        self::assertNotFalse($posNew);
        self::assertLessThan($posNew, $posOld, 'La session la plus ancienne doit apparaître en premier.');
        self::assertStringContainsString('Session du 20/08/2026', $dto->executiveSummary);
        self::assertStringContainsString("\n\n", $dto->executiveSummary, 'Les blocs de session sont séparés par une ligne vide.');
    }

    public function testMostRecentKnownRiskProfileWins(): void
    {
        $this->recordRepository->method('findActiveByFolder')->willReturn([
            $this->recording('r1', '2026-08-01 10:00:00', ['executiveSummary' => 'A', 'riskProfileDetected' => 'Prudent']),
            $this->recording('r2', '2026-08-08 10:00:00', ['executiveSummary' => 'B', 'riskProfileDetected' => 'Équilibré']),
            $this->recording('r3', '2026-08-15 10:00:00', ['executiveSummary' => 'C', 'riskProfileDetected' => 'Non déterminé']),
        ]);

        $dto = ($this->useCase)($this->createEntityState(BusinessFolder::class));

        self::assertSame('Équilibré', $dto->riskProfileDetected, "La valeur 'Non déterminé' de r3 ne doit pas écraser r2.");
    }

    public function testRiskProfileIsCanonicalisedOntoTheEnum(): void
    {
        $this->recordRepository->method('findActiveByFolder')->willReturn([
            $this->recording('r1', '2026-08-01 10:00:00', ['executiveSummary' => 'Ok.', 'riskProfileDetected' => '  equilibre ']),
        ]);

        $dto = ($this->useCase)($this->createEntityState(BusinessFolder::class));

        self::assertSame('Équilibré', $dto->riskProfileDetected);
    }

    public function testOffTopicSessionIsExcludedFromSummaryButStillCounted(): void
    {
        $this->recordRepository->method('findActiveByFolder')->willReturn([
            $this->recording('r_offtopic', '2026-08-01 10:00:00', ['executiveSummary' => 'Test ou hors sujet']),
            $this->recording('r_real', '2026-08-02 10:00:00', ['executiveSummary' => 'Entretien réel exploitable.']),
        ]);

        $dto = ($this->useCase)($this->createEntityState(BusinessFolder::class));

        self::assertTrue($dto->isExplorable);
        self::assertStringNotContainsString('hors sujet', $dto->executiveSummary);
        self::assertStringContainsString('Entretien réel exploitable', $dto->executiveSummary);
        self::assertSame(['r_offtopic', 'r_real'], $dto->slugId);
    }

    public function testRecordingsWithoutOutputAreSkipped(): void
    {
        $this->recordRepository->method('findActiveByFolder')->willReturn([
            $this->recording('r_null', '2026-08-01 10:00:00', null),
            $this->recording('r_empty', '2026-08-02 10:00:00', []),
            $this->recording('r_ok', '2026-08-03 10:00:00', ['executiveSummary' => 'Contenu exploitable.']),
        ]);

        $dto = ($this->useCase)($this->createEntityState(BusinessFolder::class));

        self::assertSame(['r_ok'], $dto->slugId);
    }

    public function testNotExplorableWhenNoUsableSummary(): void
    {
        $this->recordRepository->method('findActiveByFolder')->willReturn([
            $this->recording('r_offtopic', '2026-08-01 10:00:00', ['executiveSummary' => 'Test ou hors sujet']),
        ]);

        $dto = ($this->useCase)($this->createEntityState(BusinessFolder::class));

        self::assertFalse($dto->isExplorable);
        self::assertStringContainsString('aucune donnée exploitable', $dto->executiveSummary);
        self::assertSame('Non déterminé', $dto->riskProfileDetected);
    }

    public function testKycAndActionItemsAreTrimmedGroupedAndFreedOfEmpties(): void
    {
        $this->recordRepository->method('findActiveByFolder')->willReturn([
            $this->recording('r1', '2026-08-05 11:00:00', [
                'executiveSummary' => 'Ok.',
                'kycUpdates' => ['  Nom : Léo Garçon  ', '', '   '],
                'actionPlan' => ['Envoyer la lettre de mission', ' '],
            ]),
        ]);

        $dto = ($this->useCase)($this->createEntityState(BusinessFolder::class));

        self::assertSame([
            ['date' => '05/08/2026 à 11:00', 'items' => ['Nom : Léo Garçon']],
        ], $dto->kycUpdates);
        self::assertSame([
            ['date' => '05/08/2026 à 11:00', 'items' => ['Envoyer la lettre de mission']],
        ], $dto->actionPlan);
    }

    public function testEmptyFolderProducesEmptyStateDto(): void
    {
        $this->recordRepository->method('findActiveByFolder')->willReturn([]);

        $dto = ($this->useCase)($this->createEntityState(BusinessFolder::class));

        self::assertFalse($dto->isExplorable);
        self::assertSame([], $dto->slugId);
        self::assertSame([], $dto->kycUpdates);
        self::assertSame([], $dto->actionPlan);
        self::assertStringContainsString('aucune donnée exploitable', $dto->executiveSummary);
    }

    /**
     * @param array<string, mixed>|null $geminiRawOutput
     * @param string                    $recordedAt      heure locale Paris — le use case affiche les dates dans ce fuseau
     */
    private function recording(string $slugId, string $recordedAt, ?array $geminiRawOutput): MeetingRecording
    {
        return $this->createEntityState(MeetingRecording::class, [
            'slugId' => $slugId,
            'recordedAt' => new \DateTimeImmutable($recordedAt, new \DateTimeZone('Europe/Paris')),
            'geminiRawOutput' => $geminiRawOutput,
        ]);
    }
}
