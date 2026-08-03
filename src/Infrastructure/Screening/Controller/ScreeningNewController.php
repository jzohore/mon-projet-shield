<?php

declare(strict_types=1);

namespace App\Infrastructure\Screening\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/app/screening/new', name: 'app_screening_new', methods: ['GET', 'POST'])]
readonly class ScreeningNewController
{
    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(
        Environment $twig,
    ): Response {
        return new Response(
            $twig->render('@app/screening/screening_new.html.twig', [
                'page_title' => 'Nouvelle recherche',
            ])
        );
    }
}
