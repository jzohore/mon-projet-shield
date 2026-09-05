<?php

declare(strict_types=1);

namespace App\Tests\Domain\Screening\Entity;

use App\Domain\Screening\Entity\ScreeningAudit;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\TestCase;

final class ScreeningAuditMinimizeResultsTest extends TestCase
{
    use ReflectionHelperTrait;

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rawResults(): array
    {
        return [
            [
                'id' => 'NK-abc',
                'name' => 'Jean Dupont',
                'schema' => 'Person',
                'topics' => ['role.pep'],
                'raw_data' => ['properties' => ['birthDate' => ['1970-01-01'], 'address' => ['12 rue X, Paris']]],
            ],
            [
                'id' => 'NK-def',
                'name' => 'Jean Dupond',
                'schema' => 'Person',
                'topics' => [],
                'raw_data' => ['properties' => ['nationality' => ['fr']]],
            ],
        ];
    }

    private function audit(): ScreeningAudit
    {
        return ScreeningAudit::create(
            $this->createEntityState(Workspace::class, ['slugId' => 'wrk_1', 'name' => 'Cabinet']),
            $this->createEntityState(User::class, ['slugId' => 'usr_1', 'firstName' => 'A', 'lastName' => 'B']),
            'Jean Dupont',
            $this->rawResults(),
            2,
        );
    }

    public function testStripsRawDataAndKeepsTheSummaryAndDigest(): void
    {
        $audit = $this->audit();
        $originalDigest = hash('sha256', json_encode($this->rawResults(), \JSON_THROW_ON_ERROR));

        $audit->minimizeResults();

        self::assertTrue($audit->areResultsMinimized());
        self::assertSame($originalDigest, $audit->resultsDigest);

        foreach ($audit->results as $alert) {
            self::assertArrayNotHasKey('raw_data', $alert);
            self::assertArrayHasKey('name', $alert);
            self::assertArrayHasKey('topics', $alert);
        }
    }

    public function testIsIdempotent(): void
    {
        $audit = $this->audit();
        $audit->minimizeResults();
        $digestAfterFirst = $audit->resultsDigest;
        $minimizedAt = $audit->resultsMinimizedAt;

        $audit->minimizeResults();

        self::assertSame($digestAfterFirst, $audit->resultsDigest);
        self::assertSame($minimizedAt, $audit->resultsMinimizedAt);
    }
}
