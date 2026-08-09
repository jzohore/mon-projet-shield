<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\App\Settings;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route(path: '/app/settings/organization', name: 'app_settings_organization')]
class OrganizationController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('@app/settings/organization.html.twig', [
            'page_title' => 'Paramètres - Organisation',
            'sub_title' => 'Données légales et structurelles de votre entreprise.',
        ]);
    }
}
