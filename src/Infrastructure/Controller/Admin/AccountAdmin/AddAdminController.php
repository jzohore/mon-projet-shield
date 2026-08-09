<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin\AccountAdmin;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/admin/administrators/add', name: 'account_admin_add', methods: ['GET', 'POST'])]
final class AddAdminController
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
            $twig->render('@admin/account_admin/add_admin.html.twig', [
                'page_title' => 'Nouveau',
            ])
        );
    }
}
