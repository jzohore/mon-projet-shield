<?php

namespace App\Application\Billing\DTO\Response;

use App\Domain\Product\Entity\Product;

readonly class CreateProductResponse
{
    public string $slugId;
    public string $name;
    public string $stripePriceId;
    public int $credits;

    /**
     * On rend le constructeur privé : PHPStan est content car l'assignation
     * se fait ici, mais personne ne peut faire un "new" à l'extérieur.
     */
    private function __construct(
        string $slugId,
        string $name,
        string $stripePriceId,
        int $credits
    ) {
        $this->slugId = $slugId;
        $this->name = $name;
        $this->stripePriceId = $stripePriceId;
        $this->credits = $credits;
    }

    /**
     * La seule porte d'entrée pour créer ce DTO
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
