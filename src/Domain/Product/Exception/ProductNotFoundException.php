<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

use App\Domain\Shared\Enum\ErrorCode;
use App\Domain\Shared\Exception\AbstractDomainException;
use Symfony\Component\HttpFoundation\Response;

class ProductNotFoundException extends AbstractDomainException
{
    public static function withName(string $name): self
    {
        return new self(
            message: sprintf('Le produit "%s" est introuvable.', $name),
            errorCode: ErrorCode::PRODUCT_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND, // 404
            payload: ['searched_name' => $name]   // On donne l'info au front
        );
    }

    public static function withReference(string $reference): self
    {
        return new self(
            message: sprintf('Le produit avec la reference : "%s" est introuvable.', $reference),
            errorCode: ErrorCode::PRODUCT_NOT_FOUND,
            statusCode: Response::HTTP_NOT_FOUND, // 404
            payload: ['searched_reference' => $reference]   // On donne l'info au front
        );
    }
}
