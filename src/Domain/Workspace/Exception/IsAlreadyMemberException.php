<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Component\HttpFoundation\Response;

class IsAlreadyMemberException extends AbstractDomainException
{
    public static function withWorkspaceAndEmail(Workspace $workspace, string $email): self
    {
        return new self(
            message: sprintf('Cet utilisateur fait déjà partie de cet espace de travail : "%s.', $email),
            errorCode: ErrorCode::WORKSPACE_INVITATION_ALREADY_EXISTS,
            statusCode: Response::HTTP_FOUND,
            payload: ['workspace_name' => $workspace->name, 'email' => $email],
        );
    }
}
