<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Controller\Client;

use App\Application\Compliance\UseCase\Client\CreateFolderForClientUseCase;
use App\Domain\User\Exception\ClientNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('ROLE_USER')]
#[Route(path: '/app/clients/{slugId}/folders', name: 'app_clients_create_folder', requirements: ['slugId' => '[A-Za-z0-9_]+'], methods: ['POST'])]
final class CreateFolderForClientController extends AbstractController
{
    public function __construct(
        private readonly CreateFolderForClientUseCase $createFolderForClient,
    ) {
    }

    public function __invoke(string $slugId, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('client_new_folder_' . $slugId, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('app_clients_show', ['slugId' => $slugId]);
        }

        $type = (string) $request->request->get('type', 'individual');

        try {
            $folderSlugId = ($this->createFolderForClient)($slugId, $type);
            $this->addFlash('success', 'Nouveau dossier créé pour ce client.');

            return $this->redirectToRoute('app_compliance_method_new', [
                'type' => $type,
                'method' => 'request',
                'slugId' => $folderSlugId,
            ]);
        } catch (ClientNotFoundException) {
            throw $this->createNotFoundException();
        } catch (\DomainException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_clients_show', ['slugId' => $slugId]);
        }
    }
}
