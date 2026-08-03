<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Exception\Document;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class DocumentExistInFolderException extends AbstractDomainException
{
    public static function withName(string $name): self
    {
        return new self(
            message: sprintf('Le document de type "%s" est déjà présent dans ce dossier.', $name),
            errorCode: ErrorCode::COMPLIANCE_DOCUMENT_ALREADY_EXISTS,
            statusCode: Response::HTTP_CONFLICT, // 409 Conflict est parfait pour une erreur d'état métier
            payload: [
                'document_name' => $name,
            ]
        );
    }
}
