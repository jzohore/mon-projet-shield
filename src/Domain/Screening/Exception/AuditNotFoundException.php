<?php

namespace App\Domain\Screening\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class AuditNotFoundException extends AbstractDomainException
{
    public static function withId(Uuid $id): self
    {
        return new self(
            message: sprintf('L\'audit "%s" est introuvable.', $id),
            errorCode: ErrorCode::USER_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND, // 404
            payload: ['audit_id' => $id]
        );
    }
}
