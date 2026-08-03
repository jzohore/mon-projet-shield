<?php

declare(strict_types=1);

namespace App\Application\Billing\DTO\Response;

use App\Domain\Product\Entity\Product;
use Stripe\Price;

readonly class CreateProductResponse
{
    /**
     * On rend le constructeur privé : PHPStan est content car l'assignation
     * se fait ici, mais personne ne peut faire un "new" à l'extérieur.
     */
    private function __construct(public string $slugId, public string $name, public string|Price $stripePriceId, public int $credits)
    {
    }

    /**
     * La seule porte d'entrée pour créer ce DTO.
     */
    public static function fromEntity(Product $product): self
    {
        return new self(
            $product->slugId, // Ou $product->getSlugId() si tu as un slug
            $product->name,
            $product->stripePriceId,
            $product->credits,
        );
    }
}
