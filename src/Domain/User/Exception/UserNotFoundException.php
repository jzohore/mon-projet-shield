<?php

namespace App\Domain\User\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class UserNotFoundException extends AbstractDomainException
{
    public static function withEmail(string $email): self
    {
        return new self(
            message: sprintf('L\'utilisateur "%s" est introuvable.', $email),
            errorCode: ErrorCode::USER_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND, // 404
            payload: ['searched_slug' => $email]   // On donne l'info au front
        );
    }

    public static function withId(Uuid $id): self
    {
        return new self(
            message: sprintf('L\'utilisateur "%s" est introuvable.', $id),
            errorCode: ErrorCode::USER_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND, // 404
            payload: ['searched_slug' => $id]   // On donne l'info au front
        );
    }

    public static function withSlug(string $slug): self
    {
        return new self(
            message: sprintf('L\'utilisateur "%s" est introuvable.', $slug),
            errorCode: ErrorCode::USER_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND, // 404
            payload: ['searched_slug' => $slug]   // On donne l'info au front
        );
    }
}
