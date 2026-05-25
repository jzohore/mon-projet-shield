<?php

namespace App\Domain\Workspace\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class InvitationNotFoundException extends AbstractDomainException
{
    public static function withSlugId(string $slugId): self
    {
        return new self(
            message: 'Invitation introuvable ou expirée.',
            errorCode: ErrorCode::INVITATION_TOKEN_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND, // 404
            payload: ['invitation_slug_id' => $slugId]
        );
    }
}
