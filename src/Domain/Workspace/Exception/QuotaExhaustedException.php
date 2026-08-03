<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class QuotaExhaustedException extends AbstractDomainException
{
    public static function forWorkspace(string $workspaceName): self
    {
        return new self(
            message: sprintf('Le quota de minutes IA est épuisé pour le cabinet "%s".', $workspaceName),
            errorCode: ErrorCode::QUOTA_EXCEEDED,
            statusCode: Response::HTTP_FORBIDDEN,
            payload: ['searched_user_slug' => $workspaceName],
        );
    }
}
