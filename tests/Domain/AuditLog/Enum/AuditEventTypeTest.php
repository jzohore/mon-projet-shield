<?php

declare(strict_types=1);

namespace App\Tests\Domain\AuditLog\Enum;

use App\Domain\AuditLog\Enum\AuditEventType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * `getLabel()` et `getCategory()` sont des `match` exhaustifs (pas de `default`) :
 * un case ajouté sans libellé provoque une `\UnhandledMatchError` en production.
 */
final class AuditEventTypeTest extends TestCase
{
    /**
     * @return iterable<string, array{0: AuditEventType}>
     */
    public static function allCases(): iterable
    {
        foreach (AuditEventType::cases() as $case) {
            yield $case->name => [$case];
        }
    }

    #[DataProvider('allCases')]
    public function testEveryCaseHasALabel(AuditEventType $case): void
    {
        self::assertNotSame('', $case->getLabel());
    }

    #[DataProvider('allCases')]
    public function testEveryCaseHasACategory(AuditEventType $case): void
    {
        self::assertNotSame('', $case->getCategory());
    }

    public function testDerAcknowledgementEventsAreCategorisedUnderDer(): void
    {
        self::assertSame('DER & Signature', AuditEventType::DER_ACKNOWLEDGED->getCategory());
        self::assertSame('DER & Signature', AuditEventType::DER_ACKNOWLEDGEMENT_REVOKED->getCategory());
        self::assertSame('DER & Signature', AuditEventType::DER_DECLINED->getCategory());
    }
}
