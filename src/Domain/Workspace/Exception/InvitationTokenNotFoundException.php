<?php

declare(strict_types=1);

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
            // Le jeton est un secret d'authentification : on n'en garde qu'un préfixe de hash pour corréler.
            payload: ['token_hash' => substr(hash('sha256', $token), 0, 12)]
        );
    }
}
