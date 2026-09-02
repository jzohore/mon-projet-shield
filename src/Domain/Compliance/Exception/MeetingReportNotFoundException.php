<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

final class MeetingReportNotFoundException extends AbstractDomainException
{
    public static function withSlugId(string $slugId): self
    {
        return new self(
            message: sprintf('Le rapport d\'entretien validé "%s" est introuvable.', $slugId),
            errorCode: ErrorCode::MEETING_REPORT_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND, // 404
            payload: ['searched_slug_id' => $slugId],
        );
    }
}
