<?php

declare(strict_types=1);

namespace App\Application\Billing\DTO\Response;

use App\Domain\Product\Entity\Product;

readonly class ProductResponse
{
    public function __construct(
        public string $name,
        public string $slugId,
        public ?string $description,
        public int $credits,
        public int $priceInCents,
        public string $stripePriceId,
        public bool $isRecommended,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * Transforme l'Entité du domaine en un objet de réponse simplifié.
     */
    public static function fromEntity(Product $product): self
    {
        return new self(
            name: $product->name,
            slugId: $product->slugId,
            description: $product->description,
            credits: $product->credits,
            priceInCents: $product->priceInCents,
            stripePriceId: $product->stripePriceId,
            isRecommended: $product->isRecommended,
            createdAt: $product->createdAt,
            updatedAt: $product->updatedAt
        );
    }

    /**
     * Helper optionnel très pratique pour afficher le prix en euros dans Twig (ex: 180.00 €).
     */
    public function getPriceInEuros(): float
    {
        return $this->priceInCents / 100;
    }
}
