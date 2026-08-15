<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class WorkspaceSirenAlreadyExistsException extends AbstractDomainException
{
    public static function forSiren(string $siren): self
    {
        return new self(
            message: sprintf('Le SIRET "%s" est déjà enregistré dans notre système.', $siren),
            errorCode: ErrorCode::WORKSPACE_SIREN_ALREADY_EXISTS,
            statusCode: Response::HTTP_CONFLICT, // 409 Conflict
            payload: ['workspace_siren' => $siren]
        );
    }
}
