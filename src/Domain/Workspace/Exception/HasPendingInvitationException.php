<?php

namespace App\Domain\Workspace\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Component\HttpFoundation\Response;

class HasPendingInvitationException extends AbstractDomainException
{
    public static function withWorkspaceAndEmail(Workspace $workspace, string $email): self
    {
        return new self(
            message: sprintf('Une invitation est déjà en attente pour cette adresse email : "%s.', $email),
            errorCode: ErrorCode::WORKSPACE_HAS_PENDING_INVITATION,
            statusCode: Response::HTTP_NOT_FOUND, // 404
            payload: ['workspace_name' => $workspace->name, 'email' => $email]   // On donne l'info au front
        );
    }
}
