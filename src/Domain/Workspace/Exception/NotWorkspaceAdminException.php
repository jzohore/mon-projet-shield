<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class NotWorkspaceAdminException extends AbstractDomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Seul un administrateur de cet espace de travail peut gérer les invitations.',
            errorCode: ErrorCode::NOT_WORKSPACE_ADMIN,
            statusCode: Response::HTTP_FORBIDDEN,
        );
    }

    /** Action non ouverte au collaborateur par l'administrateur du cabinet. */
    public static function forDelegatedAction(): self
    {
        return new self(
            message: 'Cette action n\'est pas ouverte aux collaborateurs dans ce cabinet. Demandez à un administrateur.',
            errorCode: ErrorCode::NOT_WORKSPACE_ADMIN,
            statusCode: Response::HTTP_FORBIDDEN,
        );
    }
}
