<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Exception\Document;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class MandatoryDocumentDeletionException extends AbstractDomainException
{
    public static function forType(string $typeName): self
    {
        return new self(
            message: sprintf('Impossible de supprimer le document "%s" car il est requis par la réglementation LCB-FT.', $typeName),
            errorCode: ErrorCode::COMPLIANCE_DOCUMENT_MANDATORY_DELETION,
            statusCode: Response::HTTP_UNPROCESSABLE_ENTITY, // 422 Unprocessable Entity
            payload: [
                'document_type' => $typeName,
            ]
        );
    }
}
