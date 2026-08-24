<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class CannotAttachGeminiOutputException extends AbstractDomainException
{
    public static function forMeeting(): self
    {
        return new self(
            message: 'Le rapport IA est déjà attaché à cet enregistrement et ne peut être écrasé.',
            errorCode: ErrorCode::CANNOT_DELETE_ACTIVE_FOLDER,
            statusCode: Response::HTTP_NOT_FOUND, // 422
            payload: [],
        );
    }
}
