<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCase\Products;

use App\Application\Billing\DTO\Request\CreateProductRequest;
use App\Application\Billing\DTO\Response\CreateProductResponse;
use App\Domain\Product\Entity\Product;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Infrastructure\Service\Payment\Stripe\StripeService;
use Webmozart\Assert\Assert;

final readonly class CreateProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private StripeService $stripeService,
    ) {
    }

    public function __invoke(CreateProductRequest $request): CreateProductResponse
    {
        $existingProduct = $this->productRepository->getByReference($request->reference);

        if ($existingProduct instanceof Product) {
            // Le produit existe déjà, on évite de le dupliquer !
            // (Si tu as une méthode update dans ton entité, tu pourrais mettre à jour le nom ici)
            return CreateProductResponse::fromEntity($existingProduct);
        }

        Assert::notNull($request->description);

        // 4. On récupère le fameux ID généré par Stripe (ex: price_12345...)
        $generatedStripePriceId = $this->stripeService->createProduct(
            priceInCents: $request->priceInCents,
            isRecurring: $request->isRecurring,
            name: $request->name,
            description: $request->description,
            reference: $request->reference
        );

        Assert::notNull($generatedStripePriceId);

        $product = Product::create(
            reference: $request->reference,
            name: $request->name,
            credits: $request->credits,
            priceInCents: $request->priceInCents,
            stripePriceId: (string) $generatedStripePriceId,
            description: $request->description,
            isRecommended: $request->isRecommended,
            isRecurring: $request->isRecurring
        );

        $this->productRepository->save($product);

        return CreateProductResponse::fromEntity($product);
    }
}
