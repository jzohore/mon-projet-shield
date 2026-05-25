<?php

namespace App\Domain\Compliance\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

final class ComplianceFolderNotFoundException extends AbstractDomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: sprintf('Le dossier de conformité avec l\'identifiant "%s" est introuvable.', $id),
            errorCode: ErrorCode::COMPLIANCE_FOLDER_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND, // 404
            payload: ['searched_id' => $id]
        );
    }

    public static function withReference(string $reference): self
    {
        return new self(
            message: sprintf('Le dossier de conformité avec la référence "%s" est introuvable.', $reference),
            errorCode: ErrorCode::COMPLIANCE_FOLDER_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND, // 404
            payload: ['searched_reference' => $reference]
        );
    }
}
