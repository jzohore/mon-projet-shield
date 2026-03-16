<?php

namespace App\Infrastructure\Controller\Security;

use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
#[Route(path: '/logout', name: 'app_logout', methods: ['GET'])]
class LogoutController
{
    public function __invoke(
        Environment $twig,
    ): void {
        throw new \LogicException('Cette méthode est interceptée par Symfony Security.');
    }
}
