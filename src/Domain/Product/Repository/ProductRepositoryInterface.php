<?php

namespace App\Domain\Product\Repository;

use App\Domain\Product\Entity\Product;

interface ProductRepositoryInterface
{
    /**
     * @param Product $product
     * @return void
     */
    public function save(Product $product): void;

    /**
     * @param string $stripePriceId
     * @return Product|null
     */
    public function findByStripeId(string $stripePriceId): ?Product;

    /**
     * @param string $slug
     * @return Product|null
     */
    public function findOneBySlug(string $slug): ?Product;

    /**
     * @return array<int, Product>
     */
    public function findAllSortedByCredits(): array;


    /**
     * @param string $reference
     * @return Product|null
     */
    public function getByReference(string $reference): ?Product;
}
