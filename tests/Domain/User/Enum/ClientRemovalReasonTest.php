<?php

declare(strict_types=1);

namespace App\Tests\Domain\User\Enum;

use App\Domain\User\Enum\ClientRemovalReason;
use PHPUnit\Framework\TestCase;

final class ClientRemovalReasonTest extends TestCase
{
    public function testEveryCaseHasANonEmptyLabel(): void
    {
        foreach (ClientRemovalReason::cases() as $reason) {
            self::assertNotSame('', trim($reason->getLabel()));
        }
    }

    public function testValuesAreStableStructuredCodes(): void
    {
        // Ces valeurs partent dans le journal d'audit inaltérable.
        self::assertSame('creation_erronee', ClientRemovalReason::CREATION_ERRONEE->value);
        self::assertSame('doublon', ClientRemovalReason::DOUBLON->value);
        self::assertSame('jamais_entre_en_relation', ClientRemovalReason::JAMAIS_ENTRE_EN_RELATION->value);
        self::assertSame('fin_collaboration', ClientRemovalReason::FIN_COLLABORATION->value);
        self::assertSame('autre', ClientRemovalReason::AUTRE->value);
    }
}
