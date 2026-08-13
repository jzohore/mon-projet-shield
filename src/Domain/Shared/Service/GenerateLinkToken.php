<?php

declare(strict_types=1);

namespace App\Domain\Shared\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class GenerateLinkToken
{
    public function __construct(
        private UrlGeneratorInterface $router,
        #[Autowire('%env(FRONTEND_URL)%')]
        private string $frontendUrl,
    ) {
    }

    public function generate(string $routeName, string $magicLinkToken): string
    {
        $path = $this->router->generate(
            name: $routeName,
            parameters: ['token' => $magicLinkToken],
        );

        return sprintf('%s%s', rtrim($this->frontendUrl, '/'), $path);
    }

    /**
     * Génère un lien absolu vers le frontend pour les emails ou webhooks.
     *
     * @param array<string, mixed> $params Paramètres de la route (ex: ['slugId' => 'wrk_123'])
     */
    public function generateLink(string $routeName, array $params = []): string
    {
        $path = $this->router->generate(
            name: $routeName,
            parameters: $params,
        );

        return sprintf('%s%s', rtrim($this->frontendUrl, '/'), $path);
    }
}
