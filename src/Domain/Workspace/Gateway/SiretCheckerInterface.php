<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Gateway;

use App\Application\ExternalAPI\Siren\SirenResult;

interface SiretCheckerInterface
{
    public function verifyStatus(string $siret, ?string $name = null): SirenResult;
}
