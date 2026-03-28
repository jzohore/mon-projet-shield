<?php

namespace App\Infrastructure\Service;

use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Format;
use Intervention\Image\Interfaces\ImageManagerInterface;
use Intervention\Image\ImageManager;

readonly class ImageOptimizer
{
    private ImageManagerInterface $manager;

    public function __construct()
    {
        $this->manager = ImageManager::usingDriver(GdDriver::class);
    }

    public function preProcessForOcr(string $filePath): string
    {
        $image = $this->manager->decodePath($filePath);

        if ($image->width() > 2000) {
            $image->scale(width: 2000);
        }

        $encoded = $image->encodeUsingFormat(Format::JPEG, quality: 80);

        $optimizedPath = $filePath . '_optimized.jpg';
        $encoded->save($optimizedPath);

        unset($image, $encoded);

        return $optimizedPath;
    }
}
