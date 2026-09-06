<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Controller\Client;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('ROLE_USER')]
#[Route(path: '/app/clients', name: 'app_clients_list', methods: ['GET'])]
final class WorkspaceClientsController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('@app/compliance/clients_list.html.twig', [
            'page_title' => 'Mes clients',
        ]);
    }
}
