<?php

namespace App\Infrastructure\KYC\Controller;

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
        GetCurrentWorkspaceInfo
        $getCurrentWorkspaceInfo,
        #[CurrentUser]
        User
        $user,
    ): Response {
        if (null === $user->id) {
            throw new \LogicException('Anomalie système : L\'utilisateur connecté n\'a pas d\'identifiant.');
        }
        $workspace = ($getCurrentWorkspaceInfo)($user->id);

        return new Response(
            $twig->render('@app/kyc/kyc_invitation.html.twig', [
                'page_title' => 'Initier une demande KYC',
                'workspace_slug' => $workspace->slugId,
            ])
        );
    }
}
