<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Exception\Document;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class DocumentNotFoundException extends AbstractDomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: sprintf('Le document avec l\'identifiant "%s" est introuvable.', $id),
            errorCode: ErrorCode::COMPLIANCE_DOCUMENT_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND,
            payload: [
                'document_id' => $id,
            ]
        );
    }
}
