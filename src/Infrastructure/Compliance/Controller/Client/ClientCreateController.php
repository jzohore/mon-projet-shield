<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Controller\Client;

use App\Application\Compliance\DTO\Request\CreateClientRequest;
use App\Application\Compliance\UseCase\Client\CreateOrAttachClientUseCase;
use App\Infrastructure\Compliance\Form\CreateClientType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('ROLE_USER')]
#[Route(path: '/app/clients/new', name: 'app_clients_new', methods: ['GET', 'POST'], priority: 10)]
final class ClientCreateController extends AbstractController
{
    public function __construct(
        private readonly CreateOrAttachClientUseCase $createOrAttachClient,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->createForm(CreateClientType::class, new CreateClientRequest());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CreateClientRequest $dto */
            $dto = $form->getData();

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
