<?php

namespace App\Infrastructure\Ocr;

use App\Domain\Kyc\Enum\DocumentType;
use App\Domain\Port\OcrProviderInterface;

class FakeOcrProvider implements OcrProviderInterface
{
    public function extractData(DocumentType $type, string $filePath): array
    {
        // On simule une petite latence pour faire "vrai"
        usleep(500000);

        return match ($type) {
            DocumentType::ID_CARD => [
                'first_name' => 'PAUL (MOCK)',
                'last_name'  => 'ALOIS',
                'birth_date' => '1980-01-21',
                'birth_place' => 'PARIS (75)',
                'id_number'  => '130375300819',
                'nationality' => 'Française',
                'gender'     => 'M',
                'mrz' => [
                    'line_1' => 'IDFRAALOIS<<<<<<<<<<<<<<<<<<<<<<<<<<',
                    'line_2' => '1303753008192PAUL<<<<<<<<<<8001210M2',
                ],
            ],
            default => throw new \Exception("Mock non implémenté pour ce type de document"),
        };
    }
}
