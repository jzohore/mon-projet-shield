<?php

namespace App\Infrastructure\Controller\Admin\Compliance;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/admin/administrators/compliance/list', name: 'admin_compliance_list', methods: ['GET'])]
final class ComplianceListController
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
            $twig->render('@admin/billings/list.html.twig', [
                'page_title' => 'Compliance',
            ])
        );
    }
}
