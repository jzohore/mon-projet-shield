<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

final class WorkspaceNameAlreadyExistsException extends AbstractDomainException
{
    public static function forName(string $name): self
    {
        return new self(
            message: sprintf('Un espace de travail porte déjà le nom "%s".', $name),
            // Assure-toi de rajouter cette constante dans ton ErrorCode !
            errorCode: ErrorCode::WORKSPACE_NAME_ALREADY_EXISTS,
            statusCode: Response::HTTP_CONFLICT, // 409 Conflict : Parfait pour un doublon
            payload: ['workspace_name' => $name]
        );
    }
}
