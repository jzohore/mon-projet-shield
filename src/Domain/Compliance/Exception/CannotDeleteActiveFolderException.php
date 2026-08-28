<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class CannotDeleteActiveFolderException extends AbstractDomainException
{
    public static function forFolder(string $reference): self
    {
        return new self(
            message: 'Le fichier audio a déjà été supprimé',
            errorCode: ErrorCode::CANNOT_DELETE_ACTIVE_FOLDER,
            statusCode: Response::HTTP_NOT_FOUND, // 422
            payload: ['folder_reference' => $reference],
        );
    }
}
