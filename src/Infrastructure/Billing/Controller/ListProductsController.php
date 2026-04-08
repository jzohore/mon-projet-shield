<?php

namespace App\Infrastructure\Billing\Controller;

use App\Application\Billing\UseCase\FindAllSortedByCreditsUseCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/app/billing/products', name: 'app_billing_products', methods: ['GET'])]
readonly class ListProductsController
{
    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(
        Environment $twig,
        FindAllSortedByCreditsUseCase
        $findAllSortedByCreditsUseCase,
    ): Response {
        $products = ($findAllSortedByCreditsUseCase)();
        return new Response(
            $twig->render('@app/billing/product_list.html.twig', [
                'page_title' => 'Provisions de conformité',
                'page_description' => 'Sélectionnez votre allocation de crédits pour vos audits.',
                'products' => $products,
            ])
        );
    }
}
