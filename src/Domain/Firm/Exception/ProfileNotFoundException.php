<?php

declare(strict_types=1);

namespace App\Domain\Firm\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class ProfileNotFoundException extends AbstractDomainException
{
    public static function withWorkspaceName(string $name): self
    {
        return new self(
            message: sprintf('Le profil réglementaire pour l\'espace de travail "%s" est introuvable.', $name),
            errorCode: ErrorCode::WORKSPACE_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND, // 404
            payload: ['workspace_name' => $name]   // On donne l'info au front
        );
    }
}
