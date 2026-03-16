<?php

namespace App\Infrastructure\Trait;

use Symfony\Component\Uid\Ulid;

trait GenerateSlugPrefixedTrait
{
    public function generate_ulid_prefixed(string $prefix): string
    {
        $ulid = new Ulid();

        return $prefix . $ulid->toBase58();
    }
}
