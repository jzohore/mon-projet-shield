<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class InvitationAlreadyUsedException extends AbstractDomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Cette invitation a déjà été acceptée ou n\'est plus valide.',
            errorCode: ErrorCode::INVITATION_ALREADY_USED,
            statusCode: Response::HTTP_CONFLICT,
        );
    }
}
