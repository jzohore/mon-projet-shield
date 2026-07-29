<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Website;

use App\Infrastructure\Service\SitemapGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route(path: '/sitemap.xml', name: 'website_sitemap', methods: ['GET'])]
final readonly class SitemapController
{
    public function __construct(
        private SitemapGenerator $sitemapGenerator,
    ) {
    }

    public function __invoke(): Response
    {
        return new Response(
            $this->sitemapGenerator->generate(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/xml; charset=UTF-8',
            ]
        );
    }
}
