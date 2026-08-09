<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

final class ClientNotFoundException extends AbstractDomainException
{
    public static function withEmail(string $email): self
    {
        return new self(
            message: sprintf('Le client avec l\'email "%s" est introuvable.', $email),
            errorCode: ErrorCode::USER_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND,
            payload: ['searched_email' => $email]
        );
    }

    public static function withId(string $id): self
    {
        return new self(
            message: sprintf('Le client avec l\'identifiant "%s" est introuvable.', $id),
            errorCode: ErrorCode::USER_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND,
            payload: ['searched_id' => $id]
        );
    }
}
