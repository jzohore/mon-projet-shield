<?php

declare(strict_types=1);

namespace App\Domain\Billing\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionNotFoundException extends AbstractDomainException
{
    public static function withSubscriptionId(string $stripeSubscriptionId): self
    {
        return new self(
            message: sprintf('L\'abonnement "%s" est introuvable.', $stripeSubscriptionId),
            errorCode: ErrorCode::SUBSCRIPTION_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND,
            payload: ['searched_slug' => $stripeSubscriptionId]
        );
    }
}
