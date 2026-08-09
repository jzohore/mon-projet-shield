<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class CannotRevokeOwnerException extends AbstractDomainException
{
    public static function withWorkspaceAndEmail(): self
    {
        return new self(
            message: 'Vous ne pouvez pas révoquer l\'accès du gérant principal.',
            errorCode: ErrorCode::CANNOT_DELETE_ACTIVE_FOLDER,
            statusCode: Response::HTTP_FORBIDDEN, // 404
            payload: []   // On donne l'info au front
        );
    }
}
