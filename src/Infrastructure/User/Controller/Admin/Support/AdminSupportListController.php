<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Controller\Admin\Support;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/admin/support', name: 'admin_support_list', methods: ['GET'])]
final class AdminSupportListController extends AbstractController
{
    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(): Response
    {
        return $this->render('@admin/support/list.html.twig', [
            'page_title' => 'Support & Tickets',
        ]);
    }
}
