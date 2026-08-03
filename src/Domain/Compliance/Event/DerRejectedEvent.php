<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

final readonly class DerRejectedEvent
{
    public function __construct(
        private string $submissionId,
        private ?string $declineReason,
        private \DateTimeImmutable $rejectedAt,
    ) {
    }

    public function getSubmissionId(): string
    {
        return $this->submissionId;
    }

    public function getDeclineReason(): ?string
    {
        return $this->declineReason;
    }

    public function getRejectedAt(): \DateTimeImmutable
    {
        return $this->rejectedAt;
    }
}
