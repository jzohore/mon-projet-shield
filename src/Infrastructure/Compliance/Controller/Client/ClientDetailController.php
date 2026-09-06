<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Controller\Client;

use App\Application\Compliance\UseCase\Client\GetClientDetailUseCase;
use App\Domain\Compliance\Enum\RelationshipEndReason;
use App\Domain\User\Enum\ClientRemovalReason;
use App\Domain\User\Exception\ClientNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('ROLE_USER')]
#[Route(path: '/app/clients/{slugId}', name: 'app_clients_show', requirements: ['slugId' => '[A-Za-z0-9_]+'], methods: ['GET'])]
final class ClientDetailController extends AbstractController
{
    public function __construct(
        private readonly GetClientDetailUseCase $getClientDetail,
    ) {
    }

    public function __invoke(string $slugId): Response
    {
        try {
            $client = ($this->getClientDetail)($slugId);
        } catch (ClientNotFoundException) {
            throw $this->createNotFoundException();
        }

        return $this->render('@app/compliance/client_detail.html.twig', [
            'page_title' => $client->fullName,
            'client' => $client,
            'end_reasons' => RelationshipEndReason::cases(),
            'removal_reasons' => ClientRemovalReason::cases(),
        ]);
    }
}
