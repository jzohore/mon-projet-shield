<?php

namespace App\Infrastructure\Controller\App\Settings;

use App\Application\Workspace\UseCase\GetCurrentWorkspaceInfo;
use App\Domain\User\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Webmozart\Assert\Assert;

#[AsController]
#[Route(path: '/app/settings/organization', name: 'app_settings_organization')]
class OrganizationController
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
        GetCurrentWorkspaceInfo
        $getCurrentWorkspaceInfo,
    ): Response {
        $userId = $user->id;
        Assert::notNull($userId, "L'utilisateur doit avoir un ID pour récupérer le workspace.");
        $workspace = ($getCurrentWorkspaceInfo)($userId);
        return new Response(
            $twig->render('@app/settings/organization.html.twig', [
                'page_title' => 'Paramètres - Organisation',
                'sub_title' => 'Données légales et structurelles de votre entreprise.',
                'workspace' => $workspace,
            ])
        );
    }
}
