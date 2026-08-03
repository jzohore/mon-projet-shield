<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\App\Settings;

use App\Application\Workspace\DTO\Response\WorkspaceInfoResponse;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
#[Route(path: '/app/settings/billing', name: 'app_settings_billing')]
readonly class BillingController
{
    public function __construct(
        private Environment $twig,
        private CurrentWorkspaceProvider $workspaceProvider,
    ) {
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(): Response
    {
        $workspace = $this->workspaceProvider->getWorkspace();
        $dto = WorkspaceInfoResponse::fromEntity($workspace);

        return new Response(
            $this->twig->render('@app/settings/billing.html.twig', [
                'page_title' => 'Paramètres - Usage & Facturation',
                'sub_title' => 'Gérez votre solde d\'analyses KYC, rechargez vos crédits et téléchargez vos factures.',
                'workspace' => $dto,
            ])
        );
    }
}
