<?php

namespace App\Domain\Workspace\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class WorkspaceInvitationNotFoundException extends AbstractDomainException
{
    public static function withSlug(string $slug): self
    {
        return new self(
            message: sprintf('L\'espace de travail "%s" est introuvable.', $slug),
            errorCode: ErrorCode::WORKSPACE_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND, // 404
            payload: ['searched_slug' => $slug]   // On donne l'info au front
        );
    }

    public static function withId(Uuid $id): self
    {
        return new self(
            message: sprintf('L\'invitation à l\'espace de travail "%s" est introuvable.', $id),
            errorCode: ErrorCode::WORKSPACE_INVITATION_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND, // 404
            payload: ['searched_id' => $id]   // On donne l'info au front
        );
    }
}
