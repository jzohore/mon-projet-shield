<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class UserAlreadyExistsException extends AbstractDomainException
{
    public static function withEmail(string $email): self
    {
        return new self(
            message: sprintf('Un compte professionnel existe déjà avec l\'adresse email %s.', $email),
            errorCode: ErrorCode::USER_ALREADY_EXISTS,
            statusCode: Response::HTTP_CONFLICT, // 409 Conflict
            payload: ['searched_email' => $email]
        );
    }
}
