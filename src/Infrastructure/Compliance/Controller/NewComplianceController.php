<?php

namespace App\Infrastructure\Compliance\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/app/compliance/new', name: 'app_compliance_new', methods: ['GET', 'POST'])]
readonly class NewComplianceController
{
    public function __construct(
        private Environment $twig,
    ) {}

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(): Response
    {
        return new Response(
            $this->twig->render('@app/compliance/compliance_new.html.twig', [
                'page_title' => 'Nouvelle analyse de conformité',
            ])
        );
    }
}
