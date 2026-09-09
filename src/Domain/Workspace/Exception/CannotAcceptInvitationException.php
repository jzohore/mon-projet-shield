<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

/**
 * L'invitation est valide mais le contexte interdit de la consommer :
 * mauvais destinataire connecté, compte désactivé ou cabinet suspendu.
 * `Security::login()` court-circuitant le user_checker, ces gardes doivent
 * vivre dans le use case d'acceptation.
 */
class CannotAcceptInvitationException extends AbstractDomainException
{
    public static function recipientMismatch(): self
    {
        return new self(
            message: 'Vous êtes déjà connecté avec un autre compte. Déconnectez-vous pour accepter cette invitation.',
            errorCode: ErrorCode::CANNOT_ACCEPT_INVITATION,
            statusCode: Response::HTTP_CONFLICT,
        );
    }

    public static function accountDisabled(): self
    {
        return new self(
            message: 'Votre compte a été désactivé. Contactez le support KYSURE.',
            errorCode: ErrorCode::CANNOT_ACCEPT_INVITATION,
            statusCode: Response::HTTP_FORBIDDEN,
        );
    }

    public static function workspaceSuspended(): self
    {
        return new self(
            message: 'L\'accès à ce cabinet est actuellement suspendu. Contactez le support KYSURE.',
            errorCode: ErrorCode::CANNOT_ACCEPT_INVITATION,
            statusCode: Response::HTTP_FORBIDDEN,
        );
    }
}
