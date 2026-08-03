<?php

declare(strict_types=1);

namespace App\Domain\Screening\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class AuditNotFoundException extends AbstractDomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: sprintf('L\'audit "%s" est introuvable.', $id),
            errorCode: ErrorCode::USER_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND, // 404
            payload: ['audit_id' => $id]
        );
    }
}
