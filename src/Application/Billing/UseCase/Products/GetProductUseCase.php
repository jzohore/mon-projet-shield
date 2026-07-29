<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCase\Products;

use App\Application\Billing\DTO\Response\ProductResponse;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use Webmozart\Assert\Assert;

readonly class GetProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    public function __invoke(string $slugId): ProductResponse
    {
        $product = $this->productRepository->findOneBySlug($slugId);

        Assert::notNull($product, 'Le produit n\'existe pas');

        return ProductResponse::fromEntity($product);
    }
}
