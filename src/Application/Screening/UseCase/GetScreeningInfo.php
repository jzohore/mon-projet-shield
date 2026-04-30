<?php

namespace App\Application\Screening\UseCase;

use App\Application\Screening\DTO\Response\ScreeningResponse;
use App\Domain\Screening\Entity\ScreeningAudit;
use Webmozart\Assert\Assert;

readonly class GetScreeningInfo
{
    public function __invoke(ScreeningAudit $audit): ScreeningResponse
    {
        Assert::notNull($audit);
        return ScreeningResponse::fromEntity($audit);
    }
}
