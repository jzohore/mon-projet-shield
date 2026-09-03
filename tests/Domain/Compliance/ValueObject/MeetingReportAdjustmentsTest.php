<?php

declare(strict_types=1);

namespace App\Tests\Domain\Compliance\ValueObject;

use App\Domain\Compliance\Enum\AdvisoryRiskProfile;
use App\Domain\Compliance\ValueObject\MeetingReportAdjustments;
use PHPUnit\Framework\TestCase;

final class MeetingReportAdjustmentsTest extends TestCase
{
    /** @var array{summary: string, riskProfile: string, kycUpdates: array<int, mixed>, actionPlan: array<int, mixed>, slugId: array<int, string>} */
    private const array CONTENT = [
        'summary' => "Synthèse IA d'origine.",
        'riskProfile' => 'Non déterminé',
        'kycUpdates' => [['date' => '01/09/2026', 'items' => ['Nom : Léo']]],
        'actionPlan' => [],
        'slugId' => ['meeting_rec_1'],
    ];

    public function testNoneIsEmptyAndLeavesContentUntouched(): void
    {
        $adjustments = MeetingReportAdjustments::none();

        self::assertTrue($adjustments->isEmpty());
        self::assertSame(self::CONTENT, $adjustments->applyTo(self::CONTENT));
    }

    public function testBlankOrNullInputYieldsAnEmptyAdjustment(): void
    {
        self::assertTrue(MeetingReportAdjustments::fromInput(null, null)->isEmpty());
        self::assertTrue(MeetingReportAdjustments::fromInput('   ', null)->isEmpty());
        self::assertTrue(MeetingReportAdjustments::fromInput("\n\t", null)->isEmpty());
    }

    public function testSummaryOverrideIsTrimmedAndFlagsTheSnapshot(): void
    {
        $result = MeetingReportAdjustments::fromInput('  Texte revu par le CGP.  ', null)
            ->applyTo(self::CONTENT);

        self::assertSame('Texte revu par le CGP.', $result['summary']);
        self::assertSame('Non déterminé', $result['riskProfile'], 'Le profil non touché reste celui de l\'IA.');
        self::assertTrue($result['isAdjusted']);
        self::assertSame(self::CONTENT['kycUpdates'], $result['kycUpdates'], 'Les autres champs sont préservés.');
        self::assertSame(self::CONTENT['slugId'], $result['slugId']);
    }

    public function testRiskProfileOverrideWritesTheCanonicalLabel(): void
    {
        $result = MeetingReportAdjustments::fromInput(null, AdvisoryRiskProfile::DYNAMIQUE)
            ->applyTo(self::CONTENT);

        self::assertSame('Dynamique', $result['riskProfile']);
        self::assertSame("Synthèse IA d'origine.", $result['summary']);
        self::assertTrue($result['isAdjusted']);
    }

    public function testBothOverridesAreApplied(): void
    {
        $adjustments = MeetingReportAdjustments::fromInput('Nouveau texte.', AdvisoryRiskProfile::PRUDENT);

        self::assertFalse($adjustments->isEmpty());

        $result = $adjustments->applyTo(self::CONTENT);
        self::assertSame('Nouveau texte.', $result['summary']);
        self::assertSame('Prudent', $result['riskProfile']);
        self::assertTrue($result['isAdjusted']);
    }
}
