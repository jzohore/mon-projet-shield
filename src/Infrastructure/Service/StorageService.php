<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class StorageService
{
    public function storeLocally(UploadedFile $file, string $tempStorageDir, string $fileNameUniqId): string
    {
        if (!is_dir($tempStorageDir)) {
            mkdir($tempStorageDir, 0o755, true);
        }

        $extension = $file->guessExtension() ?? 'bin';
        $filename = uniqid($fileNameUniqId, true) . '.' . $extension;

        $file->move($tempStorageDir, $filename);

        return $tempStorageDir . \DIRECTORY_SEPARATOR . $filename;
    }
}
