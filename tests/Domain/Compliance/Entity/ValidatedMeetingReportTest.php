<?php

declare(strict_types=1);

namespace App\Tests\Domain\Compliance\Entity;

use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ValidatedMeetingReport;
use App\Domain\User\Entity\User;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValidatedMeetingReportTest extends TestCase
{
    use ReflectionHelperTrait;

    /** @var array{summary: string, riskProfile: string, kycUpdates: array<int, mixed>, actionPlan: array<int, mixed>, slugId: array<int, string>} */
    private const array CONTENT = [
        'summary' => "Session du 01/09/2026\nLéo, 43 ans, patrimoine locatif.",
        'riskProfile' => 'Prudent',
        'kycUpdates' => [],
        'actionPlan' => [],
        'slugId' => ['meeting_rec_1'],
    ];

    public function testValidateBuildsAFrozenInForceReport(): void
    {
        $folder = $this->createEntityState(BusinessFolder::class);
        $validator = $this->createEntityState(User::class, ['firstName' => 'marie', 'lastName' => 'curie', 'email' => 'marie@kysure.test']);

        $report = ValidatedMeetingReport::validate(
            complianceFolder: $folder,
            validatedBy: $validator,
            content: self::CONTENT,
            sourceRecordingSlugs: ['meeting_rec_1', 'meeting_rec_2'],
            version: 2,
        );

        self::assertSame(2, $report->version);
        self::assertSame($folder, $report->complianceFolder);
        self::assertSame($validator, $report->validatedBy);
        self::assertSame('Marie CURIE', $report->validatedByName);
        self::assertInstanceOf(\DateTimeImmutable::class, $report->validatedAt);
        self::assertStringStartsWith('meeting_report_', $report->slugId);
        self::assertSame(['meeting_rec_1', 'meeting_rec_2'], $report->sourceRecordingSlugs);
        self::assertSame(self::CONTENT, $report->content);
        self::assertSame(
            hash('sha256', json_encode(self::CONTENT, \JSON_THROW_ON_ERROR)),
            $report->contentHash,
        );

        self::assertTrue($report->isInForce());
        self::assertFalse($report->isRevoked());
        self::assertNull($report->revokedAt);
        self::assertNull($report->revokedByName);
        self::assertNull($report->revokeReason);
        self::assertTrue($report->matchesStoredHash());
    }

    public function testDefaultVersionIsOne(): void
    {
        $report = ValidatedMeetingReport::validate(
            $this->createEntityState(BusinessFolder::class),
            $this->createEntityState(User::class, ['firstName' => 'Jean', 'lastName' => 'Bon', 'email' => 'j@kysure.test']),
            self::CONTENT,
            [],
        );

        self::assertSame(1, $report->version);
    }

    public function testRevokeMarksReportOutOfForce(): void
    {
        $report = $this->makeReport();
        $revoker = $this->createEntityState(User::class, ['firstName' => 'paul', 'lastName' => 'martin', 'email' => 'paul@kysure.test']);

        $report->revoke($revoker, '  profil de risque erroné  ');

        self::assertTrue($report->isRevoked());
        self::assertFalse($report->isInForce());
        self::assertInstanceOf(\DateTimeImmutable::class, $report->revokedAt);
        self::assertSame('Paul MARTIN', $report->revokedByName);
        self::assertSame('profil de risque erroné', $report->revokeReason);
    }

    public function testRevokeTwiceIsRejected(): void
    {
        $report = $this->makeReport();
        $revoker = $this->createEntityState(User::class, ['firstName' => 'Paul', 'lastName' => 'Martin', 'email' => 'paul@kysure.test']);

        $report->revoke($revoker, 'première révocation');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('déjà été révoqué');

        $report->revoke($revoker, 'seconde tentative');
    }

    #[DataProvider('blankReasons')]
    public function testRevokeRequiresANonBlankReason(string $blankReason): void
    {
        $report = $this->makeReport();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('motif');

        $report->revoke(
            $this->createEntityState(User::class, ['firstName' => 'Paul', 'lastName' => 'Martin', 'email' => 'paul@kysure.test']),
            $blankReason,
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function blankReasons(): iterable
    {
        yield 'empty' => [''];
        yield 'spaces' => ['   '];
        yield 'whitespace' => ["\n\t "];
    }

    public function testFailedRevokeLeavesReportInForce(): void
    {
        $report = $this->makeReport();

        try {
            $report->revoke(
                $this->createEntityState(User::class, ['firstName' => 'Paul', 'lastName' => 'Martin', 'email' => 'paul@kysure.test']),
                '',
            );
            self::fail('Expected a DomainException.');
        } catch (\DomainException) {
            // attendu
        }

        self::assertTrue($report->isInForce());
        self::assertNull($report->revokedAt);
    }

    public function testMatchesStoredHashDetectsTampering(): void
    {
        $report = $this->makeReport();

        $tampered = self::CONTENT;
        $tampered['riskProfile'] = 'Dynamique';
        new \ReflectionProperty(ValidatedMeetingReport::class, 'content')->setValue($report, $tampered);

        self::assertFalse($report->matchesStoredHash());
    }

    private function makeReport(): ValidatedMeetingReport
    {
        return ValidatedMeetingReport::validate(
            $this->createEntityState(BusinessFolder::class),
            $this->createEntityState(User::class, ['firstName' => 'Marie', 'lastName' => 'Curie', 'email' => 'marie@kysure.test']),
            self::CONTENT,
            ['meeting_rec_1'],
        );
    }
}
