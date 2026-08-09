<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\User\Entity\User;

final readonly class DerGenerationRequestedEvent
{
    public function __construct(
        private ComplianceDocument $document,
        private User $user,
    ) {
    }

    public function getDocument(): ComplianceDocument
    {
        return $this->document;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
