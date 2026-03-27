<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Onboarding;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/app/onboarding/finalization', name: 'app_onboarding_finalization', methods: ['GET'])]
final class OnBoardingFinalizationController
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
            $twig->render('@app/onboarding/finalization.html.twig')
        );
    }
}
