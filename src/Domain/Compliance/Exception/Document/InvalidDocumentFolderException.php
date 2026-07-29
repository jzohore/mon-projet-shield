<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Exception\Document;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class InvalidDocumentFolderException extends AbstractDomainException
{
    public static function forDocument(string $documentId, string $folderSlug): self
    {
        return new self(
            message: 'Tentative de manipulation d\'un document n\'appartenant pas à ce dossier.',
            errorCode: ErrorCode::COMPLIANCE_DOCUMENT_INVALID_FOLDER,
            statusCode: Response::HTTP_FORBIDDEN, // 403 Forbidden car c'est une violation de droits
            payload: [
                'document_id' => $documentId,
                'folder_slug' => $folderSlug,
            ]
        );
    }
}
