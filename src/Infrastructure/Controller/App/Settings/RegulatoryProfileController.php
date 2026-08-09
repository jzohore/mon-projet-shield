<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\App\Settings;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route(path: '/app/settings/regulatory/profile', name: 'app_settings_regulatory_profile')]
class RegulatoryProfileController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('@app/settings/regulatory_profile.html.twig', [
            'page_title' => 'Paramètres - Profil réglementaire',
            'sub_title' => 'Vos données réglementaires, prêt sur vos templates.',
        ]);
    }
}
