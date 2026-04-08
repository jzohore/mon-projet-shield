<?php

namespace App\Application\Billing\UseCase;

use App\Application\Billing\DTO\Request\CreateProductRequest;
use App\Application\Billing\DTO\Response\CreateProductResponse;
use App\Domain\Product\Entity\Product;
use App\Domain\Product\Repository\ProductRepositoryInterface;

final readonly class CreateProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {}

    public function __invoke(CreateProductRequest $request): CreateProductResponse
    {
        $product = Product::create(
            name: $request->name,
            credits: $request->credits,
            priceInCents: $request->priceInCents,
            stripePriceId: $request->stripePriceId,
            description: $request->description,
            isRecommended: $request->isRecommended
        );

        $this->productRepository->save($product);

        return CreateProductResponse::fromEntity($product);
    }
}
