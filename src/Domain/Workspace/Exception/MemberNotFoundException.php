<?php

namespace App\Domain\Workspace\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class MemberNotFoundException extends AbstractDomainException
{
    public static function withUserSlug(string $slug): self
    {
        return new self(
            message: sprintf('Le collaborateur avec l\'identifiant "%s" est introuvable dans cet espace.', $slug),
            errorCode: ErrorCode::MEMBER_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND,
            payload: ['searched_user_slug' => $slug],
        );
    }
}
