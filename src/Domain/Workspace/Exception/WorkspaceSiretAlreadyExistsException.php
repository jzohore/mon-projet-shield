<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class WorkspaceSiretAlreadyExistsException extends AbstractDomainException
{
    public static function forSiret(string $siret): self
    {
        return new self(
            message: sprintf('Le SIRET "%s" est déjà enregistré dans notre système.', $siret),
            errorCode: ErrorCode::WORKSPACE_SIRET_ALREADY_EXISTS,
            statusCode: Response::HTTP_CONFLICT, // 409 Conflict
            payload: ['workspace_siret' => $siret]
        );
    }
}
