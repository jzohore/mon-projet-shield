<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Controller\Client;

use App\Application\Compliance\DTO\Request\CreateClientRequest;
use App\Application\Compliance\UseCase\Client\CreateOrAttachClientUseCase;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Infrastructure\Compliance\Form\CreateClientType;
use App\Infrastructure\Workspace\Voter\WorkspaceInvitationVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('ROLE_USER')]
#[Route(path: '/app/clients/new', name: 'app_clients_new', methods: ['GET', 'POST'], priority: 10)]
final class ClientCreateController extends AbstractController
{
    public function __construct(
        private readonly CreateOrAttachClientUseCase $createOrAttachClient,
        private readonly CurrentUserProvider $userProvider,
        private readonly CurrentWorkspaceProvider $workspaceProvider,
        private readonly RateLimiterFactory $clientCreationLimiter,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $this->denyAccessUnlessGranted(
            WorkspaceInvitationVoter::PORTFOLIO_MANAGE,
            $this->workspaceProvider->getWorkspace(),
        );

        $form = $this->createForm(CreateClientType::class, new CreateClientRequest());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CreateClientRequest $dto */
            $dto = $form->getData();

            // Anti-énumération d'e-mails : borne le débit d'ajouts par cabinet.
            if (!$this->clientCreationLimiter->create($this->userProvider->getUser()->slugId)->consume()->isAccepted()) {
                $this->addFlash('error', 'Trop d\'ajouts de clients en peu de temps. Réessayez plus tard.');

                return $this->redirectToRoute('app_clients_new');
            }

            try {
                $client = ($this->createOrAttachClient)($dto);
                $this->addFlash('success', 'Client ajouté à votre portefeuille.');

                return $this->redirectToRoute('app_clients_show', ['slugId' => $client->slugId]);
            } catch (\DomainException $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        return $this->render('@app/compliance/client_create.html.twig', [
            'page_title' => 'Nouveau client',
            'create_form' => $form,
        ]);
    }
}
