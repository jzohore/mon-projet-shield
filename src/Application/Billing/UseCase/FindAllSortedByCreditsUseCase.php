<?php

namespace App\Application\Billing\UseCase;

use App\Domain\Product\Entity\Product;
use App\Domain\Product\Repository\ProductRepositoryInterface;

final readonly class FindAllSortedByCreditsUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {}

    /**
     * @return array<Product>
     */
    public function __invoke(): array
    {
        return $this->productRepository->findAllSortedByCredits();
    }
}
