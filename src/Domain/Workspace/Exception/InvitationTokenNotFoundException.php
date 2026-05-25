<?php

namespace App\Domain\Workspace\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class InvitationTokenNotFoundException extends AbstractDomainException
{
    public static function withToken(string $token): self
    {
        return new self(
            message: 'Invitation introuvable ou expirée.',
            errorCode: ErrorCode::INVITATION_TOKEN_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND, // 404
            payload: ['token' => $token]
        );
    }
}
