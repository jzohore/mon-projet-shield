<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Twig;

use App\Domain\Port\DocumentStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class S3UrlExtension extends AbstractExtension
{
    public function __construct(
        private readonly DocumentStorageInterface $storage,
    ) {
    }

    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('private_url', $this->generateUrl(...)),
        ];
    }

    public function generateUrl(?string $path): string
    {
        if (!$path) {
            return '#';
        }

        return $this->storage->getTemporaryUrl($path);
    }
}
