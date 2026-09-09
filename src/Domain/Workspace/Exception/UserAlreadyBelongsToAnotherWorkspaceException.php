<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Le modèle KYSURE est « un utilisateur = un espace de travail ».
 * Rattacher un compte existant à un second cabinet casse la résolution du
 * workspace courant (requêtes « one or null ») : on refuse explicitement en amont.
 */
class UserAlreadyBelongsToAnotherWorkspaceException extends AbstractDomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Cette adresse est déjà rattachée à un autre cabinet. Un collaborateur ne peut appartenir qu\'à un seul espace de travail.',
            errorCode: ErrorCode::USER_ALREADY_IN_ANOTHER_WORKSPACE,
            statusCode: Response::HTTP_CONFLICT,
        );
    }
}
