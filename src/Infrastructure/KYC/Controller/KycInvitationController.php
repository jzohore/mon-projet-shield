<?php

namespace App\Infrastructure\KYC\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/app/kyc/invitation', name: 'app_kyc_invitation', methods: ['GET', 'POST'])]
final class KycInvitationController
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
            $twig->render('@app/kyc/kyc_invitation.html.twig', [
                'page_title' => 'Initier une demande KYC',
            ])
        );
    }
}
