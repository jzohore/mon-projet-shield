<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

final class DerCannotBeSentException extends AbstractDomainException
{
    public static function alreadySent(): self
    {
        return new self(
            message: 'Le Document d\'entrée en relation a déjà été envoyé au client.',
            errorCode: ErrorCode::DER_ALREADY_SENT,
            statusCode: Response::HTTP_CONFLICT,
            payload: []
        );
    }
}
