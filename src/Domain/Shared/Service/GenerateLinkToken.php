<?php

namespace App\Domain\Shared\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class GenerateLinkToken
{
    public function __construct(
        private UrlGeneratorInterface $router
    ) {}

    public function generate(string $routeName, string $magicLinkToken): string
    {
        return $this->router->generate($routeName, [
            'token' => $magicLinkToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
