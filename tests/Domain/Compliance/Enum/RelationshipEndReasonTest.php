<?php

declare(strict_types=1);

namespace App\Tests\Domain\Compliance\Enum;

use App\Domain\Compliance\Enum\RelationshipEndReason;
use PHPUnit\Framework\TestCase;

final class RelationshipEndReasonTest extends TestCase
{
    public function testEveryCaseHasANonEmptyLabel(): void
    {
        foreach (RelationshipEndReason::cases() as $reason) {
            self::assertNotSame('', trim($reason->getLabel()));
        }
    }

    public function testOnlyTheLcbftRiskReasonIsConfidential(): void
    {
        self::assertTrue(RelationshipEndReason::RISQUE_LCBFT->isConfidential());

        foreach (RelationshipEndReason::cases() as $reason) {
            if (RelationshipEndReason::RISQUE_LCBFT === $reason) {
                continue;
            }
            self::assertFalse($reason->isConfidential(), $reason->value . ' ne doit pas être confidentiel');
        }
    }

    public function testValuesAreStableStructuredCodes(): void
    {
        // Ces valeurs partent dans le journal d'audit inaltérable : elles ne
        // doivent jamais changer silencieusement.
        self::assertSame('fin_de_mandat', RelationshipEndReason::FIN_DE_MANDAT->value);
        self::assertSame('depart_client', RelationshipEndReason::DEPART_CLIENT->value);
        self::assertSame('non_reponse_prolongee', RelationshipEndReason::NON_REPONSE_PROLONGEE->value);
        self::assertSame('demande_client', RelationshipEndReason::DEMANDE_CLIENT->value);
        self::assertSame('risque_lcbft', RelationshipEndReason::RISQUE_LCBFT->value);
    }
}
