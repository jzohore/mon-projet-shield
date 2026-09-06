<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Controller\Client;

use App\Application\Compliance\UseCase\Client\RemoveClientFromWorkspaceUseCase;
use App\Domain\User\Enum\ClientRemovalReason;
use App\Domain\User\Exception\ClientNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
// L'autorisation « admin du cabinet » est portée par le use case
// (isUserAdminOfWorkspace) : le rôle Symfony n'est pas fiable pour le fondateur.
#[IsGranted('ROLE_USER')]
#[Route(path: '/app/clients/{slugId}/remove', name: 'app_clients_remove', requirements: ['slugId' => '[A-Za-z0-9_]+'], methods: ['POST'])]
final class RemoveClientFromWorkspaceController extends AbstractController
{
    public function __construct(
        private readonly RemoveClientFromWorkspaceUseCase $removeClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(string $slugId, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('client_remove_' . $slugId, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('app_clients_show', ['slugId' => $slugId]);
        }

        $reason = ClientRemovalReason::tryFrom((string) $request->request->get('reason', '')) ?? ClientRemovalReason::AUTRE;

        try {
            ($this->removeClient)($slugId, $reason);
            $this->addFlash('success', 'Client retiré de votre portefeuille.');

            return $this->redirectToRoute('app_clients_list');
        } catch (ClientNotFoundException) {
            throw $this->createNotFoundException();
        } catch (\DomainException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_clients_show', ['slugId' => $slugId]);
        } catch (\Throwable $exception) {
            $this->logger->error('Échec du retrait d\'un client du portefeuille.', ['error' => $exception->getMessage()]);
            $this->addFlash('error', 'Une erreur technique est survenue.');

            return $this->redirectToRoute('app_clients_show', ['slugId' => $slugId]);
        }
    }
}
