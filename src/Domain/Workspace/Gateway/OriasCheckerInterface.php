<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Gateway;

use App\Domain\Workspace\ValueObject\OriasStatusResult;

interface OriasCheckerInterface
{
    public function checkNumber(string $oriasNumber): OriasStatusResult;
}
