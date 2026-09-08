<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class SeatLimitReachedException extends AbstractDomainException
{
    public static function forWorkspace(int $usedSeats, int $allowedSeats): self
    {
        return new self(
            message: sprintf(
                'Sièges épuisés (%d / %d). Ajoutez des sièges pour inviter davantage de collaborateurs.',
                $usedSeats,
                $allowedSeats,
            ),
            errorCode: ErrorCode::SEAT_LIMIT_REACHED,
            statusCode: Response::HTTP_CONFLICT,
            payload: ['used_seats' => (string) $usedSeats, 'allowed_seats' => (string) $allowedSeats],
        );
    }
}
