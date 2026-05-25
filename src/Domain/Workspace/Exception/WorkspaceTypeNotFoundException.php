<?php

namespace App\Domain\Workspace\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class WorkspaceTypeNotFoundException extends AbstractDomainException
{
    public static function withWorkspaceType(string $workspaceType): self
    {
        return new self(
            message: sprintf('Type de structure "%s" invalide.', $workspaceType),
            errorCode: ErrorCode::WORKSPACE_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND, // 404
            payload: ['workspace_type' => $workspaceType]   // On donne l'info au front
        );
    }
}
