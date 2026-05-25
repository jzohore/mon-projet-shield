<?php

namespace App\Domain\Compliance\Event;

use App\Domain\Compliance\Entity\ComplianceFolder;
use Symfony\Contracts\EventDispatcher\Event;

class DeleteComplianceEvent extends Event
{
    public function __construct(
        public readonly ComplianceFolder $complianceFolder
    ) {}
}
