<?php

namespace App\Domain\Product\Repository;

use App\Domain\Product\Entity\Product;

interface ProductRepositoryInterface
{
    public function save(Product $product): void;
    public function findByStripeId(string $stripePriceId): ?Product;

    public function findOneBySlug(string $slug): ?Product;

    /**
     * @return array<int, Product>
     */
    public function findAllSortedByCredits(): array;
}
