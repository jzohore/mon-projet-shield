<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

final class DerAcknowledgementNotFoundException extends AbstractDomainException
{
    public static function withSlugId(string $slugId): self
    {
        return new self(
            message: sprintf('L\'accusé de réception du DER "%s" est introuvable.', $slugId),
            errorCode: ErrorCode::DER_ACKNOWLEDGEMENT_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND,
            payload: ['searched_slug_id' => $slugId],
        );
    }
}
