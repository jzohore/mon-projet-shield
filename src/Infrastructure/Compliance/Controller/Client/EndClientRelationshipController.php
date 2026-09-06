<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Controller\Client;

use App\Application\Compliance\UseCase\Client\EndClientRelationshipUseCase;
use App\Application\Compliance\UseCase\Client\GetClientDetailUseCase;
use App\Domain\Compliance\Enum\RelationshipEndReason;
use App\Domain\User\Exception\ClientNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('ROLE_WORKSPACE_ADMIN')]
#[Route(path: '/app/clients/{slugId}/end-relationship', name: 'app_clients_end_relationship', requirements: ['slugId' => '[A-Za-z0-9_]+'], methods: ['POST'])]
final class EndClientRelationshipController extends AbstractController
{
    public function __construct(
        private readonly EndClientRelationshipUseCase $endRelationship,
        private readonly GetClientDetailUseCase $getClientDetail,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(string $slugId, Request $request): RedirectResponse
    {
        $back = $this->redirectToRoute('app_clients_show', ['slugId' => $slugId]);

        if (!$this->isCsrfTokenValid('client_end_relationship_' . $slugId, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $back;
        }

        $reason = RelationshipEndReason::tryFrom((string) $request->request->get('reason', ''));
        if (!$reason instanceof RelationshipEndReason) {
            $this->addFlash('error', 'Motif de clôture invalide.');

            return $back;
        }

        // Confirmation forte : le nom du client doit être ressaisi.
        try {
            $client = ($this->getClientDetail)($slugId);
        } catch (ClientNotFoundException) {
            throw $this->createNotFoundException();
        }

        $typed = trim((string) $request->request->get('confirm_name', ''));
        if (mb_strtolower($typed) !== mb_strtolower($client->fullName)) {
            $this->addFlash('error', 'La confirmation ne correspond pas au nom du client.');

            return $back;
        }

        try {
            ($this->endRelationship)($slugId, $reason);
            $this->addFlash('success', 'Relation d\'affaires clôturée. Le client a été notifié.');
        } catch (\DomainException $exception) {
            $this->addFlash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            $this->logger->error('Échec de la clôture d\'une relation client.', ['error' => $exception->getMessage()]);
            $this->addFlash('error', 'Une erreur technique est survenue.');
        }

        return $back;
    }
}
