<?php

namespace App\Application\Screening\UseCase;

use App\Application\Screening\DTO\Response\ScreeningResponse;
use App\Domain\Screening\Repository\ScreeningAuditRepositoryInterface;
use Webmozart\Assert\Assert;

readonly class GetScreeningInfo
{
    public function __construct(
        private ScreeningAuditRepositoryInterface $screeningAuditRepository,
    ) {}

    public function __invoke(string $slugId): ScreeningResponse
    {
        $audit = $this->screeningAuditRepository->findOneBySlug($slugId);
        Assert::notNull($audit);
        return ScreeningResponse::fromEntity($audit);
    }
}
