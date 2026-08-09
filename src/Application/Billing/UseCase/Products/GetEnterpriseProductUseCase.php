<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCase\Products;

use App\Application\Billing\DTO\Response\ProductResponse;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use Webmozart\Assert\Assert;

readonly class GetEnterpriseProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    public function __invoke(): ProductResponse
    {
        $product = $this->productRepository->getByReference('plan_cabinet');
        Assert::notNull($product);

        return ProductResponse::fromEntity($product);
    }
}
