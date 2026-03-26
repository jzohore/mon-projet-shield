<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Website;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsController]
#[Route(path: '/', name: 'redirect_to_home')]
final readonly class RedirectToHomeController
{
    public function __invoke(
        UrlGeneratorInterface $urlGenerator,
    ): RedirectResponse {
        return new RedirectResponse($urlGenerator->generate('website_home'));
    }
}
