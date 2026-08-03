<?php

declare(strict_types=1);

namespace App\Application\Screening\DTO\Response;

use App\Domain\Screening\Entity\ScreeningAudit;

readonly class ScreeningResponse
{
    /**
     * @param array<int, array<string, mixed>> $alerts
     * @param array<int, array<string, mixed>> $results
     */
    private function __construct(
        public bool $isCached,
        public string $auditId,
        public ?string $pdfUrl,
        public int $totalMatches,
        public array $alerts,
        public string $statusLabel,
        public string $slugId,
        public string $status,
        public ?string $workspaceSlugId,
        public ?string $userSlugId,
        public string $query,
        public array $results = [],
        public ?string $pdfPath = null,
        public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
    ) {
    }

    public static function fromEntity(ScreeningAudit $audit, bool $isCached = false): self
    {
        return new self(
            isCached: $isCached,
            auditId: (string) $audit->id,
            pdfUrl: $audit->pdfPath,
            totalMatches: $audit->totalMatches,
            alerts: $audit->results,
            statusLabel: $audit->status->getLabel(),
            slugId: $audit->slugId,
            status: $audit->status->value,
            workspaceSlugId: $audit->workspace->slugId,
            userSlugId: $audit->owner->slugId,
            query: $audit->query,
            results: $audit->results,
            pdfPath: $audit->pdfPath,
            createdAt: $audit->createdAt,
        );
    }
}
