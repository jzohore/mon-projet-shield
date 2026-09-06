<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\Client;

use App\Application\Compliance\DTO\Request\CreateClientRequest;
use App\Domain\Compliance\Event\ClientAddedToWorkspaceEvent;
use App\Domain\User\Entity\Client;
use App\Domain\User\Entity\ClientWorkspaceRelation;
use App\Domain\User\Repository\ClientRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Ajoute un client au portefeuille du cabinet courant. S'il existe déjà dans
 * KYSURE (rattaché à un autre cabinet), on le rattache sans écraser ses données
 * maîtres et sans révéler qu'il existait — message identique dans les deux cas.
 */
readonly class CreateOrAttachClientUseCase
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
        private CurrentWorkspaceProvider $workspaceProvider,
        private CurrentUserProvider $userProvider,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(CreateClientRequest $request): Client
    {
        $workspace = $this->workspaceProvider->getWorkspace();
        $actor = $this->userProvider->getUser();
        $email = mb_strtolower(trim($request->email));

        $client = $this->clientRepository->findByEmail($email);
        $wasCreated = false;

        if (!$client instanceof Client) {
            $client = Client::initiate($email, trim($request->firstName), trim($request->lastName), isActif: true);
            $wasCreated = true;
        }

        $relation = $client->relationWith($workspace);
        if ($relation instanceof ClientWorkspaceRelation && !$relation->isEnded()) {
            return $client; // relation déjà en cours (en attente ou active) : no-op idempotent
        }

        // Nouveau client, ou client déjà connu dont la relation avec CE cabinet
        // n'existe pas / a été clôturée. La relation part EN ATTENTE : tant
        // qu'aucun DER n'est accusé, ce cabinet ne voit que le nom qu'il saisit,
        // jamais les coordonnées maîtres du compte (anti-énumération d'e-mails).
        $client->attachToWorkspace($workspace, trim($request->firstName), trim($request->lastName));

        try {
            $this->clientRepository->save($client);
        } catch (UniqueConstraintViolationException) {
            // Course : un autre membre a créé ce même e-mail entre notre lecture
            // et notre écriture. L'EntityManager est fermé — on redemande un
            // simple réessai, la seconde tentative trouvera le client existant.
            throw new \DomainException('Ce client vient d\'être ajouté par un autre membre du cabinet. Rechargez la page.');
        }

        $this->eventDispatcher->dispatch(new ClientAddedToWorkspaceEvent(
            clientSlugId: $client->slugId,
            workspaceSlugId: $workspace->slugId,
            actorName: $actor->getFullName(),
            actorSlugId: $actor->slugId,
            wasCreated: $wasCreated,
        ));

        return $client;
    }
}
