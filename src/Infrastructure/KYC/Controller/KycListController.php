<?php

namespace App\Infrastructure\KYC\Controller;

use App\Domain\User\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/app/kyc/list', name: 'app_kyc_list', methods: ['GET', 'POST'])]
final class KycListController
{
    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(
        Environment $twig,
        #[CurrentUser]
        User $user,
    ): Response {
        return new Response(
            $twig->render('@app/kyc/kyc_list.html.twig', [
                'page_title' => 'Documents KYC',
            ])
        );
    }
}
