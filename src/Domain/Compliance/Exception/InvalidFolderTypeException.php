<?php

namespace App\Domain\Compliance\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class InvalidFolderTypeException extends AbstractDomainException
{
    public static function unsupported(string $invalidType): self
    {
        return new self(
            message: sprintf("Le type de dossier '%s' n'est pas supporté par le système.", $invalidType),
            errorCode: ErrorCode::UNSUPPORTED_FOLDER_TYPE,
            statusCode: Response::HTTP_BAD_REQUEST, // 400
            payload: [
                'provided_type' => $invalidType,
                'expected_types' => ['individual', 'business'],
            ]
        );
    }
}
