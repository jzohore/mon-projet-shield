<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin\AuditLogs;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/app/administrators/audit-logs/list', name: 'admin_audit_logs_list', methods: ['GET'])]
final class AuditLogsListController
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
            $twig->render('@admin/audit_logs/list.html.twig', [
                'page_title' => 'Journal d\'audit',
            ])
        );
    }
}
