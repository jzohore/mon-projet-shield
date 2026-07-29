<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

final readonly class DerSignedEvent
{
    public function __construct(
        private string $submissionId,
        private string $documentUrl,
        private string $auditLogUrl,
        private \DateTimeImmutable $completedAt,
    ) {
    }

    public function getSubmissionId(): string
    {
        return $this->submissionId;
    }

    public function getDocumentUrl(): string
    {
        return $this->documentUrl;
    }

    public function getAuditLogUrl(): string
    {
        return $this->auditLogUrl;
    }

    public function getCompletedAt(): \DateTimeImmutable
    {
        return $this->completedAt;
    }
}
